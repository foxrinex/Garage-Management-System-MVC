<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairPartsSelected extends Model
{
    protected $table = 'repair_parts_selected';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'repair_id',
        'product_id',
        'product_name',
        'product_image',
        'category_name',
        'price_at_time',
    ];
}