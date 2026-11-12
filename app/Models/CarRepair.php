<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarRepair extends Model
{
    protected $table = 'car_repairs';
    protected $primaryKey = 'repair_id';
    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'repair_type',
        'brand',
        'model',
        'year',
        'mileage',
        'issue_description',
        'car_image',
        'status',
        'repair_notes',
        'labor_fee',
        'parts_total',
    ];
    public function customer()
    {
        // Adjust 'user_id' if your foreign key column has a different name (e.g., 'customer_id')
        return $this->belongsTo(User::class, 'user_id'); 
    }
}