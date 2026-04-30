<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'business_id',
        'kode',
        'nama',
        'parent_id',
        'jenis_mutasi',
        'no_rek_bank',
        'atas_nama_rek',
        'is_default_transfer',
        'is_default_qris',
    ];

    public function paymentsDebit()
    {
        return $this->hasMany(Payment::class, 'rekening_debit', 'kode');
    }

    public function paymentsKredit()
    {
        return $this->hasMany(Payment::class, 'rekening_kredit', 'kode');
    }

    public function balance()
    {
        return $this->hasOne(Balance::class, 'kode_akun', 'kode');
    }
}
