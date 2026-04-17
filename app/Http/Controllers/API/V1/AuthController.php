<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nip'    => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        if (!Auth::attempt($request->only('nip', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah'
            ], 401);
        }

        $user = Auth::user();

        // Hapus token lama (optional)
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data'    => [
                'user'  => $user,
                'token' => $token,
                'type'  => 'Bearer'
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout success'], 200);
    }

    public function data(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $user->loadMissing(['pegawai', 'skpd', 'roles']);

        return response()->json([
            'success' => true,
            'message' => 'Data user berhasil diambil',
            'data' => [
                'id' => $user->id,
                'nip' => $user->nip,
                'nama' => $user->nama,
                'id_role' => $user->id_role,
                'roles' => $user->roles->pluck('name')->values(),
                'id_skpd' => $user->id_skpd,
                'skpd' => [
                    'id_skpd' => $user->skpd->id_skpd ?? null,
                    'nama' => $user->skpd->nama ?? null,
                ],
                'pegawai' => [
                    'id' => $user->pegawai->id ?? null,
                    'nama' => $user->pegawai->nama ?? null,
                    'nip' => $user->pegawai->nip ?? null,
                    'id_skpd' => $user->pegawai->id_skpd ?? null,
                ],
            ]
        ], 200);
    }
}
