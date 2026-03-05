<?php

namespace App\Http\Controllers;

use App\Models\Owner;
use Auth;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login()
    {
        $owner = Owner::where('domain', request()->getHost())
            ->orWhere('domain_alternatif', request()->getHost())
            ->first();

        if (! $owner) {
            abort(404);
        }

        return view('auth.login', compact('owner'));
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

        $owner = Owner::where('domain', request()->getHost())
            ->orWhere('domain_alternatif', request()->getHost())
            ->with('businesses')
            ->first();

        if (! $owner) {
            abort(404);
        }

        if (Auth::attempt($data)) {
            $user = Auth::user();

            // Master bisa login dari domain manapun, langsung ke master dashboard
            if ($user->is_master) {
                return redirect('/master/dashboard')->with('success', 'Login berhasil!');
            }

            foreach ($owner->businesses as $business) {
                if ($business->id == $user->business_id) {
                    return redirect('/dashboard')->with('success', 'Login berhasil!');
                }
            }

            Auth::logout();

            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bisnis ini!');
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
