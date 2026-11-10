<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'product_id',
        'car_id',
        'quantity',
        'total_price',
        'date_ordered',
    ];

    /**
     * Relationship to the Product model.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * Relationship to the Car model.
     */
    public function car()
    {
        return $this->belongsTo(Car::class, 'car_id', 'id');
    }

    /**
     * Relationship to the WarrantyClaim model.
     */
    public function warrantyClaim()
    {
        return $this->hasOne(WarrantyClaim::class, 'order_id', 'id');
    }

    /**
     * Relationship to the Customer model.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'user_id');
    }
}