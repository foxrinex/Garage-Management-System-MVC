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
}