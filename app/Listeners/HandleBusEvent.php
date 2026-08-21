<?php

namespace App\Listeners;

use App\Models\Owner;
use Illuminate\Support\Facades\Log;

class HandleBusEvent
{
    public function handleInvoicePaid(array $payload): void
    {
        $this->applyMasterMeta($payload, 'active', false);
    }

    public function handleInvoiceOverdue(array $payload): void
    {
        $this->applyMasterMeta($payload, 'suspended', true);
    }

    public function handleTenantSuspendRequested(array $payload): void
    {
        $this->applyMasterMeta($payload, null, true);
    }

    public function unknown(string $eventType, array $payload): void
    {
        Log::warning('Unhandled bus event', ['event' => $eventType, 'keys' => array_keys($payload)]);
    }

    protected function applyMasterMeta(array $payload, ?string $status, bool $locked): void
    {
        $ref = (string) ($payload['tenant_ref_id'] ?? '');
        if ($ref === '') {
            return;
        }

        $owner = Owner::find($ref);
        if (! $owner) {
            return;
        }

        $existing = $owner->data ? json_decode($owner->data, true) : [];
        if ($status !== null) {
            $existing['master_status'] = $status;
        }
        $existing['is_locked_by_master'] = $locked;
        $existing['master_synced_at'] = now()->toIso8601String();

        $owner->update(['data' => json_encode($existing)]);
    }
}
