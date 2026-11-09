<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBuild extends Model
{
    protected $table = 'user_builds';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'build_name',
    ];
}