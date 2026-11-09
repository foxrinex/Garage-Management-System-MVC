<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuilderTemporary extends Model
{
    protected $table = 'builder_temporary';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'category_id',
        'product_id',
    ];
}