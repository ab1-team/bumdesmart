<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use Enpii\CrossAppBus\Facades\CrossAppBus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'nullable|string|max:64',
            'nama_usaha' => 'required|string|max:255',
            'tanggal_penggunaan' => 'nullable|date',
            'domain' => 'nullable|string|max:255',
        ]);

        $ownerId = $data['id'] ?? (string) Str::uuid();
        $domain = $data['domain'] ?? null;

        $owner = Owner::create([
            'id' => $ownerId,
            'nama_usaha' => $data['nama_usaha'],
            'tanggal_penggunaan' => $data['tanggal_penggunaan'] ?? now()->toDateString(),
            'is_locked_by_master' => false,
            'master_status' => 'active',
            'master_synced_at' => now(),
        ]);

        if ($domain) {
            $owner->domains()->create(['domain' => $domain]);
        }

        $this->emitTenantEvent('tenant.registered', $owner);

        return response()->json([
            'tenant_ref_id' => $owner->id,
            'nama_usaha' => $owner->nama_usaha,
            'domain' => $domain,
            'master_synced_at' => $this->masterData($owner)['master_synced_at'],
        ], 201);
    }

    public function update(Request $request, string $tenantRefId): JsonResponse
    {
        $owner = Owner::findOrFail($tenantRefId);

        $data = $request->validate([
            'nama_usaha' => 'sometimes|string|max:255',
            'tanggal_penggunaan' => 'sometimes|nullable|date',
            'logo' => 'sometimes|nullable|string|max:255',
        ]);

        $owner->update($data + [
            'master_synced_at' => now(),
        ]);

        return response()->json([
            'status' => 'ok',
            'tenant_ref_id' => $owner->id,
        ]);
    }

    public function toggleStatus(Request $request, string $tenantRefId): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|in:active,suspended',
        ]);

        $owner = Owner::findOrFail($tenantRefId);

        $owner->update([
            'master_status' => $data['status'],
            'is_locked_by_master' => $data['status'] === 'suspended',
            'master_synced_at' => now(),
        ]);

        $this->emitTenantEvent('tenant.status_changed', $owner);

        return response()->json([
            'status' => 'ok',
            'master_status' => $owner->master_status,
            'is_locked_by_master' => (bool) $owner->is_locked_by_master,
        ]);
    }

    public function show(string $tenantRefId): JsonResponse
    {
        $owner = Owner::findOrFail($tenantRefId);

        return response()->json([
            'tenant_ref_id' => $owner->id,
            'nama_usaha' => $owner->nama_usaha,
            'domains' => $owner->domains->pluck('domain'),
        ] + $this->masterData($owner));
    }

    public function index(Request $request): JsonResponse
    {
        $search = (string) $request->query('search', '');
        $limit = min((int) $request->query('limit', 50), 200);

        $query = Owner::query()->orderBy('nama_usaha');

        if ($search !== '') {
            $query->where('nama_usaha', 'like', "%{$search}%");
        }

        $items = $query->limit($limit)->get()->map(function ($owner) {
            $meta = $this->masterData($owner);
            $domain = $owner->domains->first()?->domain;
            return [
                'tenant_ref_id' => $owner->id,
                'label' => $domain
                    ? "[{$domain}] {$owner->nama_usaha}"
                    : $owner->nama_usaha,
                'master_status' => $meta['master_status'],
            ];
        });

        return response()->json(['data' => $items]);
    }

    protected function masterData(Owner $owner): array
    {
        $legacy = $owner->data ? json_decode($owner->data, true) : [];
        return [
            'master_status' => $owner->master_status ?? ($legacy['master_status'] ?? 'active'),
            'is_locked_by_master' => (bool) ($owner->is_locked_by_master ?? ($legacy['is_locked_by_master'] ?? false)),
            'master_synced_at' => $owner->master_synced_at?->toIso8601String()
                ?? ($legacy['master_synced_at'] ?? null),
        ];
    }

    protected function emitTenantEvent(string $eventType, Owner $owner): void
    {
        $meta = $this->masterData($owner);

        CrossAppBus::emit($eventType, [
            'tenant_ref_id' => $owner->id,
            'nama_usaha' => $owner->nama_usaha,
            'product_slug' => 'bumdesmart',
            'master_status' => $meta['master_status'],
            'is_locked_by_master' => $meta['is_locked_by_master'],
            'synced_at' => now()->toIso8601String(),
        ])->to('master');
    }
}
