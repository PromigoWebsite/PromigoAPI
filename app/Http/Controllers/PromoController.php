<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Promo;
use App\Models\Role;
use Illuminate\Support\Facades\DB;


class PromoController extends Controller
{
    public function items(Request $request){
        // dd(request()->all());
        if($request->has('page') && $request->page === "all"){
            
            $promo = Promo::join('assets','promos.id','=','assets.promo_id')
                            ->join('brands','brands.id','=','promos.brand_id')
                            ->select(
                                'brands.name as brand_name',
                                'promos.*',
                                'assets.path',
                            );
            if($request->search != ''){
                $promo = $promo->where('promos.name','LIKE','%'.$request->search.'%');
            }
            if($request->has('filter')){
                foreach($request->filter as $filterby => $filterValue){
                    if ($filterValue === '' || $filterValue === null) {
                        continue;
                    }
                    if($filterby == 'brand'){
                        $promo = $promo->where('brands.name',$filterValue);
                    }else{
                        $promo = $promo->where('promos.'.$filterby,$filterValue);
                    }
                   
                }
            }
            if($request->sort != ''){
                $promo = $promo->orderBy('created_at',$request->sort);
            }
            $promo = $promo->get();
            // dd($promo);
            return response()->json($promo);
        }
        if($request->has('search')){
            $promo = Promo::where('name','%'.$request->search.'%');
        }
        else{
            $promo = Promo::join('assets','promos.id','=','assets.promo_id')
                        ->select(
                            'promos.*',
                            'assets.path',
                        );
        }
        if($request->has('filter')){
            foreach($request->filter as $key => $value){
                $promo = $promo->where($key,$value);
            }
        }
        if($request->has('sorting')){
            foreach($request->sorting as $key => $value){
                $promo = $promo->orderBy($key,$value)
                            ->paginate($request->per_page);
            }
        }
        else{
            $promo = $promo->orderBy('name','ASC')
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
                                ->skip(1)
                                ->take(4)
                                ->get();
        
        return response()->json($recommendation);
    }

    //promodetail
    public function promoDetail($id)
    {
        $promo = Promo::join('brands' , 'promos.brand_id', '=' , 'brands.id')
                    ->select('promos.id', 'promos.name' , 'promos.diskon' , 'promos.description' , 'promos.status', 'promos.started_at', 'promos.ended_at')
                    ->where('promos.id', $id)
                    ->first(); 
        
        return response()->json($promo);
    }
}

