<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $table = 'cart';
    protected $primaryKey = 'id';
    public $incrementing = true; // Set to false if 'id' is not auto-incrementing in your DB
    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'product_id',
        'quantity',
    ];
}