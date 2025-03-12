<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    use HasFactory;
    protected $fillable = [
        'brand_id',
        'nama_promo',
        'diskon',
        'description',
        'status',
        'started_at',
    ];
}
