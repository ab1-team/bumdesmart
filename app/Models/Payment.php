<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'user_id',
        'no_pembayaran',
        'tanggal_pembayaran',
        'jenis_transaksi',
        'transaction_id',
        'total_harga',
        'metode_pembayaran',
        'no_referensi',
        'catatan',
        'rekening_debit',
        'rekening_kredit',
    ];

    public function accountDebit()
    {
        return $this->belongsTo(Account::class, 'rekening_debit', 'kode');
    }

    public function accountKredit()
    {
        return $this->belongsTo(Account::class, 'rekening_kredit', 'kode');
    }

    public function jurnal()
    {
        return $this->belongsTo(Jurnal::class, 'transaction_id', 'id');
    }

    public function inventaris()
    {
        return $this->belongsTo(Inventory::class, 'transaction_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getJurnalAttribute()
    {
        if ($this->jenis_transaksi !== 'jurnal') {
            return null;
        }

        return $this->getRelationValue('jurnal');
    }

    public function getInventarisAttribute()
    {
        if ($this->jenis_transaksi !== 'inventaris') {
            return null;
        }

        return $this->getRelationValue('inventaris');
    }
}
