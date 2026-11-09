<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'category_name',
        'type',
        'is_mandatory',
        'category_img',
    ];
}