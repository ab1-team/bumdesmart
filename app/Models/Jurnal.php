<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jurnal extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'tanggal',
        'keterangan',
        'relasi',
        'jumlah',
        'urutan',
        'user_id',
    ];

    public function getPayment()
    {
        return $this->hasOne(Payment::class, 'transaction_id', 'id')->where('jenis_transaksi', 'jurnal');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
