<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'business_id',
        'role_id',
        'nama_lengkap',
        'initial',
        'no_hp',
        'username',
        'password',
        'foto',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
