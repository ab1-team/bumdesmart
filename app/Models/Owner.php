<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Owner extends BaseTenant implements TenantWithDatabase
{
    use HasFactory, HasDatabase, HasDomains;

    protected $table = 'owners';

    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    public function getIncrementing()
    {
        return false;
    }

    protected $guarded = [];

    public function businesses()
    {
        return $this->hasMany(Business::class);
    }

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'nama_usaha',
            'tanggal_penggunaan',
            'logo',
            'master_synced_at',
            'is_locked_by_master',
            'master_status',
        ];
    }
}
