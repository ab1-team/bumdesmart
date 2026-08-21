<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddMasterSyncToOwnersTable extends Migration
{
    public function up(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->timestamp('master_synced_at')->nullable();
            $table->boolean('is_locked_by_master')->default(false);
            $table->string('master_status', 32)->default('active');
        });

        // Backfill dari JSON `data` lama ke kolom fisik (satu kali, idempotent).
        DB::table('owners')->whereNotNull('data')->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $row) {
                $legacy = json_decode($row->data, true);
                if (! is_array($legacy)) {
                    continue;
                }

                $syncedAt = null;
                if (! empty($legacy['master_synced_at'])) {
                    try {
                        $syncedAt = \Carbon\Carbon::parse($legacy['master_synced_at']);
                    } catch (\Throwable $e) {
                        $syncedAt = null;
                    }
                }

                DB::table('owners')->where('id', $row->id)->update([
                    'is_locked_by_master' => (bool) ($legacy['is_locked_by_master'] ?? false),
                    'master_status' => (string) ($legacy['master_status'] ?? 'active'),
                    'master_synced_at' => $syncedAt,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->dropColumn(['master_synced_at', 'is_locked_by_master', 'master_status']);
        });
    }
}