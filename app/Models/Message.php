<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'message';
    protected $primaryKey = 'message_id';
    public $timestamps = false;

    protected $fillable = [
        'sender_full_name',
        'sender_email',
        'message',
        'date_time',
        'status',
    ];
}