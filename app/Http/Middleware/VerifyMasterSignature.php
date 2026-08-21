<?php

namespace App\Http\Middleware;

use Closure;
use Enpii\CrossAppBus\Support\HmacSigner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyMasterSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $appKey = $request->header('X-Bus-App-Key');
        $signature = $request->header('X-Bus-Signature');
        $timestamp = (int) $request->header('X-Bus-Timestamp');

        $expectedKey = (string) config('cross-app-bus.app_key');
        if ($expectedKey === '' || ! hash_equals($expectedKey, (string) $appKey)) {
            $this->audit($request, 'invalid_app_key');
            return response()->json(['error' => 'invalid_app_key'], 401);
        }

        $body = $request->getContent();

        $valid = HmacSigner::verify(
            $body,
            (string) config('cross-app-bus.app_secret'),
            $signature,
            $timestamp,
            (int) config('cross-app-bus.signature_skew_seconds', 300),
        );

        if (! $valid) {
            $this->audit($request, 'invalid_signature');
            return response()->json(['error' => 'invalid_signature'], 401);
        }

        return $next($request);
    }

    protected function audit(Request $request, string $reason): void
    {
        Log::warning('Master signature rejected', [
            'reason' => $reason,
            'ip' => $request->ip(),
            'path' => $request->path(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
