<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Promo;
use App\Models\Role;

class PromoController extends Controller
{
    public function items(Request $request){
        if($request->has('page') && $request->page === "all"){
            $promo = Promo::get();
            return response()->json($promo);
        }
        if($request->has('search')){
            $searchValue = $request->search;
            $promo = Promo::where(function ($query) use($searchValue){
                $query->where('nama_promo',$searchValue);
            });
        }
        else{
            $promo = Promo::query();
        }
        if($request->has('filter')){
            foreach($request->filter as $key => $value){
                $promo = $promo->where($key,$value);
            }
        }
        if($request->has($sorting)){
            foreach($request->sorting as $key => $value){
                $promo = $promo->orderBy($key,$value);
            }
        }
        else{
            $promo = $promo->orderBy('nama_promo','ASC')
                            ->paginate($request->per_page);
        }
        return respose()->json($promo);
    }
}
