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

    /**
     * Relasi ke Payment berdasarkan payment_id (Foreign Key langsung).
     * Digunakan untuk validasi rekening_debit / rekening_kredit
     * sehingga bisa membedakan Aset Tetap (1.2.01.x) vs Aset Tak Berwujud (1.2.03.x).
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'id');
    }
}
