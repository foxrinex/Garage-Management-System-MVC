<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Customer extends Model
{
    protected $table = 'customer';
    protected $primaryKey = 'user_id';
    public $incrementing = false; // Set to true if your table has a separate auto-incrementing id column
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'fname',
        'lname',
        'phone_number',
        'email_address',
        'address',
        'gender',
    ];

    /**
     * Relationship to the User model.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'uid');
    }
}
