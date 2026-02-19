<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function auth()
    {
        return view('pages.auth.login');
    }

    public function login(Request $request)
    {
        if (
            !Auth::attempt([
                'email' => $request->email,
                'password' => $request->password,
            ])
        ) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth');
    }
}
