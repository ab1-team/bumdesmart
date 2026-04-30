<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, SoftDeletes;

    protected $fillable = [
        'is_master',
        'business_id',
        'role_id',
        'nama_lengkap',
        'initial',
        'no_hp',
        'email',
        'alamat',
        'username',
        'password',
        'foto',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
