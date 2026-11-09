<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'message';
    public $timestamps = false; // Assuming legacy table doesn't have Laravel created_at/updated_at columns

    protected $fillable = [
        'sender_full_name',
        'sender_email',
        'message',
    ];
}