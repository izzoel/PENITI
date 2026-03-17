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
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
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

    public function dataUser(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        $user->loadMissing(['skpd.kepalaUser']);

        return response()->json([
            'success' => true,
            'message' => 'Profil user',
            'data'    => [
                'id'                  => $user->id,
                'name'                => $user->name,
                'email'               => $user->email,
                'nip'                 => $user->nip,
                'jabatan'             => $user->jabatan,
                'role'                => $user->role,
                'unit_kerja'          => $user->unit_kerja,
                'skpd_id'             => $user->skpd_id,
                'skpd_name'           => optional($user->skpd)->nama,
                'skpd_kepala_user_id' => optional($user->skpd)->kepala_user_id,
                'skpd_kepala_nama'    => optional($user->skpd?->kepalaUser)->name,
                'supervisor_id'       => $user->supervisor_id,
                'supervisor_nama'     => $user->supervisor?->name,
                'phone'               => $user->phone,
                'address'             => $user->address,
                // sesuaikan bila kolom ini ada:
                // 'tmt_mulai'           => optional($user->tmt_mulai)->toDateString(),
                // 'masa_kerja_label'    => $user->masa_kerja_label ?? null,
            ],
        ], 200);
    }
}
