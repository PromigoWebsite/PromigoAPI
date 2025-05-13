<?php

namespace App\Http\Controllers;

use App\Models\Promo;
use App\Models\Report;
use Exception;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function addReport(Request $request, $id){
        try {
            $request->validate([
                'value' => 'required'
            ]);
    
            $promo = Promo::join('brands','brands.id','=','promos.brand_id')
                        ->join('users','users.id','=','brands.user_id')
                        ->select(
                            'users.id as user_id',
                            'brands.id as brand_id',
                        )
                        ->where('promos.id',$id)
                        ->first();
    
            $report = [
                'user_id' => $promo->user_id,
                'promo_id' => $id,
                'brand_id' => $promo->brand_id,
                'status' => 'On going',
                'description' => $request->value,
            ];
    
            Report::create($report);
        } catch (Exception $e) {
            throw $e;
        }
        
    }
}
