<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DriveController extends Controller
{
    public function fileUpload(Request $request){
        // $path = Storage::disk('supabase')->url('lala.txt');
        $path = Storage::disk('supabase')->put('/test/testfinal.txt','hahahaha');
        return response()->json($path);

    }
}
