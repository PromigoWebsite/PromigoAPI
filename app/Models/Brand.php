<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model {
    use HasFactory;
    protected $fillable = [
        'user_id',
        'name',
        'address',
        'logo',
        'description',
        'category',
    ];
}
