<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'parent_id',
        'category_id',
        'brand_id',
        'unit_id',
        'shelf_id',
        'sku',
        'barcode',
        'nama_produk',
        'harga_beli',
        'harga_jual',
        'stok_minimal',
        'stok_aktual',
        'metode_biaya',
        'biaya_rata_rata',
        'gambar',
        'is_active',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function shelf()
    {
        return $this->belongsTo(Shelves::class);
    }

    public function productPrices()
    {
        return $this->hasMany(ProductPrice::class);
    }
}
