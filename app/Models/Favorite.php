<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    use HasFactory;
    public $incrementing = false; 
    protected $primaryKey = null; 
    protected $fillable = [
        'user_id',
        'promo_id',
    ];
}
