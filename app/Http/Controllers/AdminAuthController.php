<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function loginForm()
    {
        return view('auth.admin-login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if ($request->email !== env('ADMIN_EMAIL')) {
            return back()->with('error', 'Email atau password salah.');
        }

        if (!Hash::check($request->password, env('ADMIN_PASSWORD_HASH'))) {
            return back()->with('error', 'Email atau password salah.');
        }

        $request->session()->regenerate();

        session([
            'admin_login' => true,
            'admin_email' => $request->email,
        ]);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['admin_login', 'admin_email']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}