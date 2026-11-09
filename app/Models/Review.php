<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $table = 'reviews';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'car_id',
        'user_id',
        'rating',
        'comment',
        'company_reply',
        'date_posted',
    ];
}