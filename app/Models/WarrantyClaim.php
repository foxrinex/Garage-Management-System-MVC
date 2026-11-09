<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarrantyClaim extends Model
{
    protected $table = 'warranty_claims';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'customer_id',
        'claim_type',
        'reason',
        'status',
        'date_submitted',
    ];
}