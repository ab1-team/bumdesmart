<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'jumlah',
        'harga_satuan',
        'jenis_diskon',
        'jumlah_diskon',
        'jenis_cashback',
        'jumlah_cashback',
        'subtotal',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function purchasesReturnDetail()
    {
        return $this->hasOne(PurchasesReturnDetail::class);
    }

    public function purchasesReturnDetails()
    {
        return $this->hasMany(PurchasesReturnDetail::class);
    }

    public function productBatch()
    {
        return $this->hasOne(ProductBatch::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
