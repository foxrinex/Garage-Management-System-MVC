<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $table = 'tags';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'tag_name',
    ];
}