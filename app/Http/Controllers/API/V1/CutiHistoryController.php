<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CutiHistoryItemResource;
use App\Models\PermohonanCuti;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// class CutiHistoryController extends Controller
// {
//     /**
//      * GET /api/history
//      * Query params:
//      * - page (default 1)
//      * - per_page (default 15, max 100)
//      * - status (optional) salah satu dari status di sistem
//      * - q (optional) cari di alasan / nama / NIP / jenis
//      * - date_from, date_to (optional, format Y-m-d) filter berdasarkan tanggal_mulai
//      */
//     public function index(Request $r)
//     {
//         $user    = Auth::user();

//         // validasi query ringan
//         $r->validate([
//             'per_page'  => 'nullable|integer|min:1|max:100',
//             'status'    => 'nullable|string|in:draft,diajukan,disetujui_atasan,disetujui_kepala,ditangguhkan_atasan,ditangguhkan_kepala,ditolak_atasan,ditolak_kepala,dibatalkan',
//             'q'         => 'nullable|string|max:100',
//             'date_from' => 'nullable|date',
//             'date_to'   => 'nullable|date|after_or_equal:date_from',
//         ]);

//         $perPage = (int) ($r->per_page ?? 15);

//         $q = PermohonanCuti::query()
//             ->with(['pemohon:id,name,nip,skpd_id,role,supervisor_id', 'pemohon.skpd:id,nama', 'atasan:id,name', 'kepalaSkpd:id,name'])
//             // filter teks
//             ->when($r->filled('q'), function ($qq) use ($r) {
//                 $s = trim($r->q);
//                 $qq->where(function ($w) use ($s) {
//                     $w->where('alasan', 'like', "%{$s}%")
//                       ->orWhere('jenis', 'like', "%{$s}%")
//                       ->orWhereHas('pemohon', function ($u) use ($s) {
//                           $u->where('name','like',"%{$s}%")
//                             ->orWhere('nip','like',"%{$s}%");
//                       });
//                 });
//             })
//             // filter status
//             ->when($r->filled('status'), fn($qq) => $qq->where('status', $r->status))
//             // filter rentang tanggal_mulai
//             ->when($r->filled('date_from'), fn($qq) => $qq->whereDate('tanggal_mulai', '>=', $r->date_from))
//             ->when($r->filled('date_to'),   fn($qq) => $qq->whereDate('tanggal_mulai', '<=', $r->date_to));

//         // BATASAN cakupan sesuai role
//         switch ($user->role) {
//             case 'admin':
//                 // lihat semua (tanpa batasan)
//                 break;

//             case 'kepala_skpd':
//                 $q->whereHas('pemohon', fn($u) => $u->where('skpd_id', $user->skpd_id));
//                 // kecuali user admin
//                 $q->whereHas('pemohon', fn($u) => $u->where('role', '<>', 'admin'));
//                 break;

//             case 'atasan':
//                 $q->whereHas('pemohon', fn($u) => $u->where('supervisor_id', $user->id));
//                 $q->whereHas('pemohon', fn($u) => $u->where('role', '<>', 'admin'));
//                 break;

//             default: // pns
//                 $q->where('user_id', $user->id);
//                 break;
//         }

//         // urut terbaru
//         $p = $q->latest()->paginate($perPage)->appends($r->query());

//         return response()->json([
//             'success' => true,
//             'message' => 'Riwayat cuti',
//             'data' => [
//                 'items'        => CutiHistoryItemResource::collection($p->items()),
//                 'current_page' => $p->currentPage(),
//                 'per_page'     => $p->perPage(),
//                 'last_page'    => $p->lastPage(),
//                 'total'        => $p->total(),
//             ],
//         ]);
//     }

//     /**
//      * GET /api/history/{cuti}
//      * Ikuti policy 'view' yang sudah kamu punya.
//      */
//     public function show(PermohonanCuti $cuti)
//     {
//         $cuti->load(['pemohon:id,name,nip,skpd_id,role,supervisor_id', 'pemohon.skpd:id,nama', 'atasan:id,name', 'kepalaSkpd:id,name']);
//         $this->authorize('view', $cuti);

//         // larang non-admin melihat milik user admin (sesuai aturan sistem)
//         if (Auth::user()->role !== 'admin' && $cuti->pemohon?->role === 'admin') {
//             abort(403);
//         }

//         return response()->json([
//             'success' => true,
//             'message' => 'Detail cuti',
//             'data'    => new CutiHistoryItemResource($cuti),
//         ]);
//     }
// }
class CutiHistoryController extends Controller
{
    public function index(Request $r)
    {
        $user = Auth::user();

        // authorize viewAny jika pakai Policy
        // $this->authorize('viewAny', PermohonanCuti::class);

        $q = PermohonanCuti::query()
            ->with([
                // batasi kolom agar hemat
                'pemohon:id,name,nip,skpd_id,role,supervisor_id',
                'pemohon.skpd:id,nama',
                'atasan:id,name',
                'kepalaSkpd:id,name',
            ])
            ->when($r->filled('q'), function ($qq) use ($r) {
                $s = trim($r->q);
                $qq->where(function ($w) use ($s) {
                    $w->where('jenis', 'like', "%{$s}%")
                        ->orWhere('alasan', 'like', "%{$s}%")
                        ->orWhereHas('pemohon', function ($u) use ($s) {
                            $u->where('name', 'like', "%{$s}%")
                                ->orWhere('nip', 'like', "%{$s}%");
                        });
                });
            })
            ->when($r->filled('status'), fn($qq) => $qq->where('status', $r->status));

        // Non-admin jangan lihat milik admin
        if ($user->role !== 'admin') {
            $q->whereHas('pemohon', fn($u) => $u->where('role', '!=', 'admin'));
        }

        // Opsional: dukung query scope
        // - mine: milik sendiri
        // - subordinates: bawahan langsung (atasan)
        // - skpd: 1 SKPD (kepala)
        // - all: semua (hanya admin)
        $scope = $r->string('scope')->toString();
        if ($scope === 'mine') {
            $q->where('user_id', $user->id);
        } elseif ($scope === 'subordinates' && $user->role === 'atasan') {
            $q->whereHas('pemohon', fn($u) => $u->where('supervisor_id', $user->id));
        } elseif ($scope === 'skpd' && $user->role === 'kepala_skpd') {
            $q->whereHas('pemohon', fn($u) => $u->where('skpd_id', $user->skpd_id));
        } elseif ($scope === 'all' && $user->role === 'admin') {
            // no extra filter
        } else {
            // Default (tanpa scope eksplisit): GABUNG (OR) sesuai role
            switch ($user->role) {
                case 'admin':
                    // lihat semua
                    break;

                case 'kepala_skpd':
                    $q->where(function ($w) use ($user) {
                        $w->where('user_id', $user->id)
                            ->orWhereHas('pemohon', fn($u) => $u->where('skpd_id', $user->skpd_id));
                    });
                    break;

                case 'atasan':
                    $q->where(function ($w) use ($user) {
                        $w->where('user_id', $user->id)
                            ->orWhereHas('pemohon', fn($u) => $u->where('supervisor_id', $user->id));
                    });
                    break;

                default: // 'pns'
                    $q->where('user_id', $user->id);
                    break;
            }
        }

        $perPage = min((int) $r->input('per_page', 15), 100);
        $rows = $q->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Riwayat cuti',
            'data' => [
                'items'        => CutiHistoryItemResource::collection($rows->items()),
                'current_page' => $rows->currentPage(),
                'per_page'     => $rows->perPage(),
                'last_page'    => $rows->lastPage(),
                'total'        => $rows->total(),
            ],
        ]);
    }

    public function show(PermohonanCuti $cuti)
    {
        $cuti->load([
            'pemohon:id,name,nip,skpd_id,role,supervisor_id',
            'pemohon.skpd:id,nama',
            'atasan:id,name',
            'kepalaSkpd:id,name',
        ]);

        // authorize detail
        $this->authorize('view', $cuti);

        // larang non-admin melihat milik user admin
        if (Auth::user()->role !== 'admin' && $cuti->pemohon?->role === 'admin') {
            abort(403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail cuti',
            'data'    => new CutiHistoryItemResource($cuti),
        ]);
    }
}
