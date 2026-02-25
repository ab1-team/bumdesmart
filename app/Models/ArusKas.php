<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArusKas extends Model
{
    use HasFactory;

    protected $fillable = [
        'id', 'nama_akun', 'urutan', 'sub', 'super_sub', 'status',
    ];

    public function rekenings()
    {
        return $this->hasMany(ArusKasRekening::class, 'arus_kas_id');
    }
}
