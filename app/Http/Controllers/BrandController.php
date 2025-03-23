<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Brand;
use Illuminate\Support\Facades\Response;

class BrandController extends Controller
{
    public function items(Request $request){
        if($request->has('page') && $request->page === "all"){
            $brand = Brand::get();
            return response()->json($brand);
        }
        if($request->has('search')){
            $searchValue = $request->search;
            $brand = Brand::where(function ($query) use($searchValue){
                $query->where('nama_promo',$searchValue);
            });
        }
        else{
            $brand = Brand::query();
        }
        if($request->has('filter')){
            foreach($request->filter as $key => $value){
                $brand = $brand->where($key,$value);
            }
        }
        if($request->has('sorting')){
            foreach($request->sorting as $key => $value){
                $brand = $brand->orderBy($key,$value);
            }
        }
        else{
            $brand = $brand->orderBy('name','ASC')
                            ->paginate($request->per_page);
        }
        return Response()->json($brand);
    }
}
