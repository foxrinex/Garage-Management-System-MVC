<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'customer';
    protected $primaryKey = 'id'; // or 'customer_id' if that's what your table uses
    public $timestamps = false; // Set to true if your table has created_at/updated_at

    protected $fillable = [
        'user_id',
        'fname',
        'lname',
        'phone_number',
        'email_address',
        'address',
        'gender',
    ];
}