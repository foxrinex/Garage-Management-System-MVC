<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    protected $table = 'cars';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'brand',
        'model',
        'year',
        'category_id',
        'price',
        'quantity',
        'status',
        'transmission',
        'mileage',
        'description',
        'regular_warranty',
        'replacement_warranty',
        'image',
        'in_auction',
    ];

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class, 'product_id');
    }
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}