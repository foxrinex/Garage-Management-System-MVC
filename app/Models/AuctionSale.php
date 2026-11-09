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
}