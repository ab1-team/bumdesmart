<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class SsoController extends Controller
{
    public function consume(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $request->validate(['token' => 'required|string']);

        $verify = Http::withHeaders([
            'X-Bus-App-Key' => (string) config('cross-app-bus.app_key'),
        ])->post(
            rtrim((string) config('cross-app-bus.targets.master.base_url'), '/') . '/api/master/verify-token',
            ['token' => $request->token],
        );

        if (! $verify->successful() || ! $verify->json('valid')) {
            return response()->json(['error' => 'token_invalid'], 403);
        }

        $adminUser = User::where('is_master', true)->orderBy('id')->first();

        if (! $adminUser) {
            return response()->json(['error' => 'no_master_admin_user'], 500);
        }

        Auth::loginUsingId($adminUser->id);

        return redirect('/master');
    }
}
