<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuctionBid extends Model
{
    protected $table = 'auction_bids';
    protected $primaryKey = 'id';
    public $timestamps = false; // Database handles bid_time via default current_timestamp()

    protected $fillable = [
        'auction_id',
        'customer_id',
        'bid_amount',
    ];
}