<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;
    
    protected $fillable= [
        'public_id',
        'asset_id',
        'path',
        'mime_type',
        'size',
        'file_name',
        'promo_id',
    ];
}
