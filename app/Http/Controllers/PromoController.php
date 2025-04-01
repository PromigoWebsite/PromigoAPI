<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Promo;
use App\Models\Role;
use Illuminate\Support\Facades\DB;


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
        if($request->has('sorting')){
            foreach($request->sorting as $key => $value){
                $promo = $promo->orderBy($key,$value);
            }
        }
        else{
            $promo = $promo->orderBy('nama_promo','ASC')
                            ->paginate($request->per_page);
        }
        return response()->json($promo);
    }

    public function newestPromo(Request $request){
        $newestPromo = Promo::join('assets','promos.id','=','assets.promo_id')
                            ->select(
                                'promos.*',
                                'assets.path',
                            )
                            ->orderBy('created_at','DESC')
                            ->take(4)
                            ->get();

        return response()->json($newestPromo);
    }

    public function recommendation(Request $request){
        $recommendation = Promo::join('assets', 'promos.id', '=', 'assets.promo_id')
                                ->join('brands','brands.id','=','promos.brand_id')
                                ->joinSub(function($query) {
                                    $query->select(
                                        'promo_id',
                                        DB::raw('COUNT(*) as wishlist_count'),
                                        )
                                        ->from('wishlists')
                                        ->groupBy('promo_id');
                                }, 'wish_counts', function($join) {
                                    $join->on('promos.id', '=', 'wish_counts.promo_id');
                                })
                                ->select(
                                    'promos.*',
                                    'assets.path',
                                    'brands.name as brand_name',
                                    'brands.logo',
                                )
                                ->orderBy('wish_counts.wishlist_count', 'DESC')
                                ->take(4)
                                ->get();
        
        return response()->json($recommendation);
    }

    //promodetail
    public function promoDetail($id)
    {
        $promo = Promo::join('brands' , 'promos.brand_id', '=' , 'brands.id')
                    ->select('promos.id', 'promos.nama_promo' , 'promos.diskon' , 'promos.description' , 'promos.status', 'promos.started_at', 'promos.ended_at')
                    ->where('promos.id', $id)
                    ->first(); 
        
        return response()->json($promo);
    }
}

