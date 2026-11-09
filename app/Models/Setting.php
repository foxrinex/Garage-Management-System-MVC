<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'setting';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'garage_name',
        'slogan',
        'about',
        'current_year',
    ];
}