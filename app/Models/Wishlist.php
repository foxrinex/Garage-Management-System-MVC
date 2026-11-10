<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    protected $table = 'wishlist';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'product_id',
        'car_id',
        'date_added',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function car()
    {
        return $this->belongsTo(Car::class, 'car_id');
    }
}