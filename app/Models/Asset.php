<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;
    
    protected $fillable= [
        'promo_id',
        'path',
        'mime_type',
        'size',
        'file_name',
    ];
}
