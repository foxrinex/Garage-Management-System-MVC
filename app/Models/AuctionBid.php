<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuctionBid extends Model
{
    protected $table = 'auction_bids';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'auction_id',
        'customer_id',
        'bid_amount',
        'bid_time',
    ];
}