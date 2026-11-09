<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Manager extends Model
{
    protected $table = 'manager';
    protected $primaryKey = 'manager_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'fname',
        'lname',
        'email_address',
        'phone_number',
        'gender',
        'date_of_joined',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'uid');
    }
}