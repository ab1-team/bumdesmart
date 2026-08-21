<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $checks = [
            'app' => 'ok',
            'database' => $this->checkDb(),
            'queue' => $this->checkQueue(),
        ];

        $healthy = ! in_array('fail', $checks, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }

    protected function checkDb(): string
    {
        try {
            DB::connection()->getPdo();
            return 'ok';
        } catch (\Throwable $e) {
            return 'fail';
        }
    }

    protected function checkQueue(): string
    {
        try {
            $size = Queue::connection()->size();
            return $size > 10000 ? 'fail' : 'ok';
        } catch (\Throwable $e) {
            return 'fail';
        }
    }
}
