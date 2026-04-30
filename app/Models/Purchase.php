<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'no_pembelian',
        'tanggal_pembelian',
        'business_id',
        'supplier_id',
        'user_id',
        'jenis_pembayaran',
        'subtotal',
        'jenis_diskon',
        'jumlah_diskon',
        'jenis_cashback',
        'jumlah_cashback',
        'jumlah_pajak',
        'total',
        'dibayar',
        'kembalian',
        'jumlah_utang',
        'status',
        'keterangan',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function purchaseDetails()
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    public function purchaseReturn()
    {
        return $this->hasMany(PurchasesReturn::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'transaction_id', 'id')->where('jenis_transaksi', 'purchase');
    }

    public function stockMovement()
    {
        return $this->hasMany(StockMovement::class, 'reference_id', 'id')->where('reference_type', 'purchase');
    }
}
