<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//profile
class Profile extends Model
{
    use HasFactory;
    protected $fillable = [
       'username', 
       'fullname',
       'role',
       'email', 
       'mobile' 
    ];
}
