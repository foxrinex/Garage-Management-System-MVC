<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'uid';
    public $timestamps = false;

    protected $fillable = [
        'username',
        'password',
        'user_type',
    ];

    protected $hidden = [
        'password',
    ];

    // Role profile relationships
    public function manager()
    {
        return $this->hasOne(Manager::class, 'user_id', 'uid');
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'user_id', 'uid');
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id', 'uid');
    }
}