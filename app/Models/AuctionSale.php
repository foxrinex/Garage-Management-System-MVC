<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuctionSale extends Model
{
    protected $table = 'auction_sales';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'auction_id',
        'car_id',
        'customer_id',
        'sale_price',
        'sale_date',
    ];
    public function car()
    {
        return $this->belongsTo(Car::class, 'car_id'); // Adjust 'car_id' if your foreign key column has a different name
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id'); // Change Customer::class to User::class if your customer table uses the User model
    }
}