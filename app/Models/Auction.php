<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auction extends Model
{
    protected $table = 'auctions';
    protected $primaryKey = 'id';
    public $timestamps = false; // Database handles created_at via default current_timestamp()

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
}