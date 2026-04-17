<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CutiSaldo;
use App\Models\CutiJenisSetting;
use App\Services\CutiQuotaService;
use App\Models\User;

class CutiSaldoController extends Controller
{
    private const JENIS = ['tahunan', 'besar', 'sakit', 'melahirkan', 'penting', 'diluar_tanggungan'];

    /**
     * GET /api/saldo-cuti
     * Mengembalikan saldo per-jenis untuk user yang sedang login.
     * Query: ?tahun=YYYY (opsional, default tahun ini)
     */
    public function me(Request $request)
    {
        $user  = $request->user();                    // ← pastikan pakai guard API/Sanctum
        $tahun = (int) $request->input('tahun', now()->year);

        // Ambil kuota default per-jenis dari tabel cuti_jenis_settings
        $settings = CutiJenisSetting::where('aktif', true)
            ->pluck('kuota_hari', 'jenis');

        $data = [];
        foreach (self::JENIS as $j) {
            $kuota = (int) ($settings[$j] ?? 0);
            $sisa  = (int) CutiQuotaService::sisaUser($user->id, $j, $tahun);
            $terpakai = max(0, $kuota - $sisa);

            $row = [
                'jenis'     => $j,
                'label'     => ucwords(str_replace('_', ' ', $j)),
                'tahun'     => $tahun,
                'kuota'     => $kuota,
                'terpakai'  => $terpakai,
                'sisa'      => $sisa,
            ];

            // Untuk 'tahunan' sertakan snapshot N/N-1/N-2
            if ($j === 'tahunan') {
                $snap = CutiQuotaService::snapshotTahunanSettings($user->id, $tahun);
                $row['snapshot'] = [
                    'n_2' => (int) ($snap[$tahun - 2]['sisa'] ?? 0),
                    'n_1' => (int) ($snap[$tahun - 1]['sisa'] ?? 0),
                    'n'   => (int) ($snap[$tahun]['sisa'] ?? 0),
                ];
            }

            $data[] = $row;
        }

        return response()->json([
            'success' => true,
            'message' => "Saldo cuti per-jenis ($tahun)",
            'tahun'   => $tahun,
            'user'    => [
                'id'   => $user->id,
                'name' => $user->name,
                'nip'  => $user->nip,
            ],
            'data'    => $data,
        ], 200);
    }

    /**
     * (Opsional) GET /api/saldo-cuti/{user}
     * Untuk admin/atasan/kepala melihat saldo user tertentu (cek akses dasar).
     */
    public function byUser(Request $request, User $user)
    {
        $me = $request->user();
        if (! in_array($me->role, ['admin', 'atasan', 'kepala_skpd'], true)) {
            abort(403);
        }
        if ($me->role !== 'admin') {
            // batasan: atasan hanya bawahannya; kepala hanya SKPD yang sama
            $boleh = ($me->role === 'atasan' && $user->supervisor_id === $me->id)
                || ($me->role === 'kepala_skpd' && $user->skpd_id === $me->skpd_id);
            if (! $boleh) abort(403);
        }

        // Reuse logika 'me' tapi untuk $user lain
        $tahun    = (int) $request->input('tahun', now()->year);
        $settings = CutiJenisSetting::where('aktif', true)->pluck('kuota_hari', 'jenis');

        $data = [];
        foreach (self::JENIS as $j) {
            $kuota = (int) ($settings[$j] ?? 0);
            $sisa  = (int) CutiQuotaService::sisaUser($user->id, $j, $tahun);
            $terpakai = max(0, $kuota - $sisa);

            $row = [
                'jenis'     => $j,
                'label'     => ucwords(str_replace('_', ' ', $j)),
                'tahun'     => $tahun,
                'kuota'     => $kuota,
                'terpakai'  => $terpakai,
                'sisa'      => $sisa,
            ];

            if ($j === 'tahunan') {
                $snap = CutiQuotaService::snapshotTahunanSettings($user->id, $tahun);
                $row['snapshot'] = [
                    'n_2' => (int) ($snap[$tahun - 2]['sisa'] ?? 0),
                    'n_1' => (int) ($snap[$tahun - 1]['sisa'] ?? 0),
                    'n'   => (int) ($snap[$tahun]['sisa'] ?? 0),
                ];
            }

            $data[] = $row;
        }

        return response()->json([
            'success' => true,
            'message' => "Saldo cuti per-jenis ($tahun) untuk {$user->name}",
            'tahun'   => $tahun,
            'user'    => [
                'id'   => $user->id,
                'name' => $user->name,
                'nip'  => $user->nip,
            ],
            'data'    => $data,
        ], 200);
    }

    /**
     * GET /api/v1/cuti/saldo/{id_user}
     * Ambil saldo cuti user langsung dari tabel cuti_saldos.
     */
    public function saldoByUser(Request $request, int $id_user)
    {
        $me = $request->user();

        if ((int) $me->id !== $id_user && ! in_array($me->role, ['admin', 'atasan', 'kepala_skpd'], true)) {
            abort(403);
        }

        $user = User::find($id_user);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan',
            ], 404);
        }

        if ((int) $me->id !== $id_user && $me->role !== 'admin') {
            $boleh = ($me->role === 'atasan' && (int) $user->supervisor_id === (int) $me->id)
                || ($me->role === 'kepala_skpd' && (int) $user->skpd_id === (int) $me->skpd_id);

            if (! $boleh) {
                abort(403);
            }
        }

        $saldos = CutiSaldo::with(['kuotas', 'skpd'])
            ->where('id_user', $id_user)
            ->orderByDesc('tahun')
            ->orderBy('id_cuti_kuota')
            ->get();

        return response()->json([
            'success' => true,
            'message' => "Saldo cuti user {$id_user}",
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'nip' => $user->nip,
            ],
            'data' => $saldos->map(function (CutiSaldo $saldo) {
                return [
                    'id' => $saldo->id,
                    'id_user' => $saldo->id_user,
                    'id_cuti_kuota' => $saldo->id_cuti_kuota,
                    'jenis_cuti' => $saldo->kuotas->jenis ?? null,
                    'id_skpd' => $saldo->id_skpd,
                    'skpd' => $saldo->skpd->nama ?? null,
                    'tahun' => $saldo->tahun,
                    'kuota' => $saldo->kuota,
                    'terpakai' => $saldo->terpakai,
                    'sisa' => $saldo->sisa,
                    'expired' => optional($saldo->expired)->toDateString(),
                    'created_at' => optional($saldo->created_at)->toDateTimeString(),
                    'updated_at' => optional($saldo->updated_at)->toDateTimeString(),
                ];
            })->values(),
        ], 200);
    }
}
