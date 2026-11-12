<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auction extends Model
{
    protected $table = 'auctions';
    protected $primaryKey = 'id';
    public $timestamps = false; // Database handles created_at via default current_timestamp()
    const UPDATED_AT = null;

    protected $fillable = [
        'car_id',
        'start_price',
        'current_bid',
        'reserve_price',
        'min_increment',
        'highest_bidder_id',
        'start_time',
        'end_time',
        'status',
        'winner_paid',
    ];
    public function car()
    {
        return $this->belongsTo(Car::class, 'car_id', 'id');
    }

    public function highestBidder()
    {
        // 'highest_bidder_id' is the foreign key in the auctions table
        // 'uid' is the primary key in the users table
        return $this->belongsTo(User::class, 'highest_bidder_id', 'uid');
    }
    public function bids()
    {
        return $this->hasMany(AuctionBid::class, 'auction_id', 'id');
    }
}