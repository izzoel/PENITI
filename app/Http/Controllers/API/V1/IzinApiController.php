<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermohonanCutiResource;
use App\Models\Entries;
use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IzinApiController extends Controller
{
    public function riwayat(Request $request, $id_pegawai)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $pegawai = Pegawai::where('id', $id_pegawai)->first();

        if (!$pegawai) {
            return response()->json([
                'success' => false,
                'message' => 'Data pegawai tidak ditemukan atau tidak sesuai dengan user login'
            ], 404);
        }

        $riwayat = Entries::with(['pegawai', 'kuota', 'skpd'])
            ->where('id_pegawai', $pegawai->id)
            ->whereHas('kuota', function ($query) {
                $query->whereRaw('LOWER(jenis) = ?', ['sakit']);
            })
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Riwayat izin berhasil diambil',
            'data' => PermohonanCutiResource::collection($riwayat),
        ], 200);
    }
}
