<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login()
    {
        if (auth()->check()) {
            return redirect('/dashboard');
        }

        if (tenant()) {
            $owner = tenant();

            return view('auth.login', compact('owner'));
        }

        // Central Login (for Master Admins)
        return view('auth.login');
    }

    public function auth(Request $request)
    {
        $data = $request->only([
            'username',
            'password',
        ]);

        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        if (tenant()) {
            // Tenant Login
            if (Auth::attempt($data)) {
                $user = Auth::user();

                // Ensure it's not a master user in tenant DB (unless you allow it)
                if ($user->is_master) {
                    return redirect('/master/dashboard')->with('success', 'Login berhasil!');
                }

                return redirect('/dashboard')->with('success', 'Login berhasil!');
            }
        } else {
            // Central Login (using a different guard if needed, or manual check)
            // For simplicity, let's assume central users are in 'central_users' table
            // and we use a 'central' guard or just check the model.

            // Note: You should configure 'central' guard in config/auth.php
            if (Auth::guard('central')->attempt($data)) {
                return redirect('/master/dashboard')->with('success', 'Login berhasil!');
            }
        }

        return redirect()->back()->with('error', 'Username atau password salah!');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
