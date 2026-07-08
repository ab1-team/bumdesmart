<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventories';

    protected $fillable = [
        'business_id',
        'payment_id',
        'nama_barang',
        'tanggal_beli',
        'tanggal_validasi',
        'jumlah',
        'harga_satuan',
        'umur_ekonomis',
        'jenis',
        'kategori',
        'status',
    ];

    public function getPayment()
    {
        return $this->hasOne(Payment::class, 'transaction_id', 'id');
    }
}
