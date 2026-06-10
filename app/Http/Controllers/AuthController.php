<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function login(): View
    {
        return view('auth.login');
    }

    public function masuk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([...$data, 'aktif' => true], $request->boolean('ingat'))) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak sesuai, atau akun belum aktif.',
            ]);
        }

        $request->session()->regenerate();
        ActivityLog::catat($request, null, 'Login', 'User masuk ke sistem.');

        return redirect()->intended(route('dashboard'));
    }

    public function keluar(Request $request): RedirectResponse
    {
        ActivityLog::catat($request, null, 'Logout', 'User keluar dari sistem.');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
