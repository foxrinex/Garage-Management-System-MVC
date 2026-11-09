<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'employees';
    protected $primaryKey = 'employee_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'fname',
        'lname',
        'email_address',
        'phone_number',
        'salary',
        'date_of_joined',
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'uid');
    }
}