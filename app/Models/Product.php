<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'product_name',
        'description',
        'price',
        'quantity',
        'image',
        'category_id',
        'status',
        'regular_warranty',
        'replacement_warranty',
        'tags',
        'hp',
    ];
}