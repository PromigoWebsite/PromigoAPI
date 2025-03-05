<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Testpertama extends Controller
{
    public function test(){
        return response()->json(["test" => "lala"]);
    }
}
