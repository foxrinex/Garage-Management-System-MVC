<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuildItem extends Model
{
    protected $table = 'build_items';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'build_id',
        'product_id',
    ];
}