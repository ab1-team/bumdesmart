<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'transaction_id', 'id')->where('jenis_transaksi', 'sale');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'reference_id', 'id')->where('reference_type', 'sale');
    }

    public function saleReturn()
    {
        return $this->hasOne(SalesReturn::class);
    }
}
