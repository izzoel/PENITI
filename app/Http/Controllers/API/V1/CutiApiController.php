<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\CutiKuota;
use App\Models\CutiSaldo;
use App\Models\Entries;
use App\Models\Pegawai;
use App\Models\SKPD as Skpd;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Http\Resources\PermohonanCutiResource;
use App\Services\CutiService;
use App\Services\CutiQuotaService;
use App\Services\DocVerifyService;
use Spatie\Permission\Models\Role;

class CutiApiController extends Controller
{
    private function userCanAccessEntriesAll($user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'super admin'])) {
            return true;
        }

        if (empty($user->id_role)) {
            return false;
        }

        return Role::query()
            ->where('id', $user->id_role)
            ->whereIn('name', ['admin', 'super admin'])
            ->exists();
    }

    private function updateSaldo($id_user, $id_cuti_kuota, $tahun, $jumlah)
    {
        $saldo = CutiSaldo::where('id_user', $id_user)
            ->where('id_cuti_kuota', $id_cuti_kuota)
            ->where('tahun', $tahun)
            ->first();

        // Kalau tidak ada saldo → skip / atau bisa create baru
        if (!$saldo) {
            return;
        }

        // Kurangi saldo
        $saldo->sisa = max(0, $saldo->sisa - $jumlah);
        $saldo->terpakai = ($saldo->terpakai ?? 0) + $jumlah;

        $saldo->save();
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_pegawai' => 'required|exists:pegawais,id',
            'id_cuti_kuota' => 'required|exists:cuti_kuotas,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date',
            'lama_hari' => 'required|integer|min:1',
            'alasan' => 'required|string',
            'alamat' => 'required|string',
            'telp' => 'required|string',
            'lampiran_path' => 'nullable|file|mimes:pdf|max:4096',
        ]);

        $tahun = format_tahun($request->tanggal_mulai);

        $pegawai = Pegawai::find($request->id_pegawai);
        $id_atasan = empty($pegawai->id_atasan) ? 1 : $pegawai->id_atasan;
        $id_user = $pegawai->id ?? auth()->id();
        $cutiKuota = CutiKuota::find($request->id_cuti_kuota);
        $isCutiSakit = strtolower(trim($cutiKuota->jenis ?? '')) === 'sakit';

        $atasan = Pegawai::where('id_struktur', $id_atasan)->first();

        // Ambil saldo cuti
        $saldos = CutiSaldo::with('kuotas')
            ->where('id_user', $id_user)
            ->where('id_cuti_kuota', $request->id_cuti_kuota)
            ->whereIn('tahun', [$tahun, $tahun - 1, $tahun - 2])
            ->get()
            ->keyBy('tahun');

        $n   = $saldos[$tahun]->sisa ?? 0;
        $n_1 = min($saldos[$tahun - 1]->sisa ?? 0, 6);
        $n_2 = min($saldos[$tahun - 2]->sisa ?? 0, 6);

        $lama_hari_tersisa = (int) $request->lama_hari;
        $pakai_n = 0;
        $pakai_n1 = 0;
        $pakai_n2 = 0;
        $sisa_n = $n;
        $sisa_n1 = $n_1;
        $sisa_n2 = $n_2;

        if (!$isCutiSakit) {
            $pakai_n = min($lama_hari_tersisa, $n);
            $lama_hari_tersisa -= $pakai_n;
            $sisa_n = $n - $pakai_n;

            $pakai_n1 = min($lama_hari_tersisa, $n_1);
            $lama_hari_tersisa -= $pakai_n1;
            $sisa_n1 = $n_1 - $pakai_n1;

            $pakai_n2 = min($lama_hari_tersisa, $n_2);
            $lama_hari_tersisa -= $pakai_n2;
            $sisa_n2 = $n_2 - $pakai_n2;
        }

        if (!$isCutiSakit && $lama_hari_tersisa > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Saldo cuti tidak mencukupi'
            ], 422);
        }

        $lampiranPath = null;

        if ($request->hasFile('lampiran_path')) {
            $folder = Str::slug($pegawai->skpd->nama ?? 'umum') . '/lampiran/sakit';
            $fileName = Str::slug($pegawai->nama ?? 'pegawai')
                . '--Lampiran-Sakit--'
                . date('Y-m-d', strtotime($request->tanggal_mulai))
                . '.'
                . $request->file('lampiran_path')->getClientOriginalExtension();

            $lampiranPath = $request->file('lampiran_path')->storeAs($folder, $fileName, 'public');
        }

        $entry = DB::transaction(function () use ($request, $pegawai, $id_atasan, $atasan, $id_user, $tahun, $pakai_n, $pakai_n1, $pakai_n2, $sisa_n, $sisa_n1, $sisa_n2, $lampiranPath) {
            $entry = Entries::create([
                'id_pegawai' => $request->id_pegawai,
                'id_cuti_kuota' => $request->id_cuti_kuota,
                'id_skpd' => $pegawai->id_skpd,
                'id_atasan' => $id_atasan,
                'tanggal_mulai' => $request->tanggal_mulai,
                'tanggal_selesai' => $request->tanggal_selesai,
                'lama_hari' => $request->lama_hari,
                'alasan' => $request->alasan,
                'alamat' => $request->alamat,
                'telp' => $request->telp,
                'lampiran_path' => $lampiranPath,
                'kepada_nama' => $atasan->nama ?? '-',
                'n' => $sisa_n,
                'n_1' => $sisa_n1,
                'n_2' => $sisa_n2,
            ]);

            $entry->update([
                'nomor_surat' => nomor_surat($entry),
                'status' => 'diproses'
            ]);

            if ($pakai_n > 0) {
                $this->updateSaldo($id_user, $request->id_cuti_kuota, $tahun, $pakai_n);
            }

            if ($pakai_n1 > 0) {
                $this->updateSaldo($id_user, $request->id_cuti_kuota, $tahun - 1, $pakai_n1);
            }

            if ($pakai_n2 > 0) {
                $this->updateSaldo($id_user, $request->id_cuti_kuota, $tahun - 2, $pakai_n2);
            }

            return $entry->fresh();
        });

        // === RESPONSE API ===
        return response()->json([
            'success' => true,
            'message' => 'Pengajuan cuti berhasil ditambahkan',
            'data' => $entry
        ], 201);
    }

    public function riwayat(Request $request, $id_pegawai)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $pegawai = Pegawai::where('id', $id_pegawai)
            ->first();

        if (!$pegawai) {
            return response()->json([
                'success' => false,
                'message' => 'Data pegawai tidak ditemukan atau tidak sesuai dengan user login'
            ], 404);
        }

        $riwayat = Entries::with(['pegawai', 'kuota', 'skpd'])->where('id_cuti_kuota', '!=', 4)
            ->where('id_pegawai', $pegawai->id)
            ->orderByDesc('created_at')->get();

        return response()->json([
            'success' => true,
            'message' => 'Riwayat cuti berhasil diambil',
            'data' => PermohonanCutiResource::collection($riwayat),

        ], 200);
    }

    public function saldo(Request $request, $id_pegawai)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $pegawai = Pegawai::with('skpd')->where('id', $id_pegawai)->first();

        if (!$pegawai) {
            return response()->json([
                'success' => false,
                'message' => 'Data pegawai tidak ditemukan'
            ], 404);
        }

        $saldo = CutiSaldo::with(['kuotas', 'skpd'])
            ->where('id_user', $pegawai->id)
            ->orderByDesc('tahun')
            ->orderBy('id_cuti_kuota')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Saldo cuti berhasil diambil',
            'pegawai' => [
                'id' => $pegawai->id,
                'nama' => $pegawai->nama,
                'nip' => $pegawai->nip,
                'id_skpd' => $pegawai->id_skpd,
                'skpd' => $pegawai->skpd->nama ?? null,
            ],
            'data' => $saldo->map(function ($item) {
                return [
                    'id' => $item->id,
                    'id_user' => $item->id_user,
                    'id_cuti_kuota' => $item->id_cuti_kuota,
                    'jenis_cuti' => $item->kuotas->jenis ?? null,
                    'id_skpd' => $item->id_skpd,
                    'skpd' => $item->skpd->nama ?? null,
                    'tahun' => $item->tahun,
                    'kuota' => $item->kuota,
                    'terpakai' => $item->terpakai,
                    'sisa' => $item->sisa,
                    'expired' => optional($item->expired)->toDateString(),
                    'created_at' => optional($item->created_at)->toDateTimeString(),
                    'updated_at' => optional($item->updated_at)->toDateTimeString(),
                ];
            })->values(),
        ], 200);
    }

    public function jenis(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $jenis = CutiKuota::query()
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Jenis cuti berhasil diambil',
            'data' => $jenis,
        ], 200);
    }

    public function entries(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $query = Entries::with(['pegawai', 'kuota', 'skpd'])
            ->whereIn('status', ['diajukan', 'diproses'])
            ->get();


        return response()->json([
            'success' => true,
            'message' => 'Entries cuti berhasil diambil',
            'data' => PermohonanCutiResource::collection($query),
        ], 200);
    }

    public function entriesUp(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $query = Entries::with(['pegawai', 'kuota', 'skpd']);

        if ($request->filled('id_pegawai')) {
            $query->where('id_pegawai', $request->id_pegawai);
        } elseif (!empty($user->id_pegawai)) {
            $query->where('id_pegawai', $user->id_pegawai);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $latestUpdatedAt = (clone $query)->max('updated_at');

        $entries = $latestUpdatedAt
            ? $query->where('updated_at', $latestUpdatedAt)
            ->orderByDesc('updated_at')
            ->get()
            : collect();

        return response()->json([
            'success' => true,
            'message' => 'Entries cuti berhasil diambil',
            'data' => PermohonanCutiResource::collection($entries),
        ], 200);
    }

    public function entriesAll(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        if (!$this->userCanAccessEntriesAll($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke data ini'
            ], 403);
        }

        $entries = Entries::with(['pegawai', 'kuota', 'skpd'])
            ->where('id_skpd', $user->id_skpd)
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Semua entry cuti berhasil diambil',
            'data' => PermohonanCutiResource::collection($entries),
        ], 200);
    }

    public function verifikasi(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $data = $request->validate([
            'id' => 'required|exists:entries,id',
            'aksi' => ['nullable', Rule::in(['draft', 'diajukan', 'diproses', 'disetujui', 'ditolak'])],
        ]);

        $entry = Entries::with(['pegawai', 'kuota', 'skpd'])->find($data['id']);

        if (!$entry) {
            return response()->json([
                'success' => false,
                'message' => 'Entry cuti tidak ditemukan'
            ], 404);
        }

        $update = [];

        if (!empty($data['aksi'])) {
            $update['status'] = $data['aksi'];
        } else {
            switch ($entry->status) {
                case 'draft':
                    $update['status'] = 'diajukan';
                    break;
                case 'diajukan':
                    $update['status'] = 'diproses';
                    $update['disetujui_atasan_at'] = now();
                    break;
                case 'diproses':
                    $update['status'] = 'disetujui';
                    $update['disetujui_pimpinan_at'] = now();
                    break;
                case 'disetujui':
                    $update['status'] = 'ditolak';
                    break;
                case 'ditolak':
                    $update['status'] = 'disetujui';
                    $update['disetujui_atasan_at'] = $entry->disetujui_atasan_at ?? now();
                    $update['disetujui_pimpinan_at'] = now();
                    break;
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Status entry tidak valid untuk diverifikasi'
                    ], 422);
            }
        }

        if (($update['status'] ?? null) === 'diproses' && empty($update['disetujui_atasan_at'])) {
            $update['disetujui_atasan_at'] = now();
        }

        if (($update['status'] ?? null) === 'disetujui' && empty($update['disetujui_pimpinan_at'])) {
            $update['disetujui_pimpinan_at'] = now();
        }

        $entry->update($update);

        if (($update['status'] ?? null) === 'disetujui') {
            DocVerifyService::issue($entry->fresh());
        }

        return response()->json([
            'success' => true,
            'message' => 'Verifikasi cuti berhasil diproses',
            'data' => new PermohonanCutiResource($entry->fresh(['pegawai', 'kuota', 'skpd'])),
        ], 200);
    }
}
