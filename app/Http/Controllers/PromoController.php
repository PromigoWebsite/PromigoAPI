<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Favorite;
use Illuminate\Http\Request;
use App\Models\Promo;
use App\Models\Role;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\DB;


class PromoController extends Controller {
    public function items(Request $request) {
        // dd(request()->all());
        if ($request->has('page') && $request->page === "all") {

            $promo = Promo::join('assets', 'promos.id', '=', 'assets.promo_id')
                ->join('brands', 'brands.id', '=', 'promos.brand_id')
                ->select(
                    'brands.name as brand_name',
                    'promos.id',
                    'promos.name',
                    'assets.path',
                );
            if ($request->search != '') {
                $promo = $promo->whereRaw('LOWER(promos.name) LIKE ?', ['%' . strtolower($request->search) . '%']);
            }
            if ($request->has('filter')) {
                foreach ($request->filter as $filterby => $filterValue) {
                    if ($filterValue === '' || $filterValue === null) {
                        continue;
                    }
                    if ($filterby == 'brand') {
                        $promo = $promo->where('brands.name', $filterValue);
                    } else {
                        $promo = $promo->where('promos.' . $filterby, $filterValue);
                    }
                }
            }
            if ($request->sort != '') {
                $promo = $promo->orderBy('created_at', $request->sort);
            }
            $promo = $promo->get();
            // dd($promo);
            return response()->json($promo);
        }
        if ($request->has('search')) {
            $promo = Promo::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($request->search) . '%']);
        } else {
            $promo = Promo::join('assets', 'promos.id', '=', 'assets.promo_id')
                ->join('brands', 'brands.id', '=', 'promos.brand_id')
                ->select(
                    'brands.name as brand_name',
                    'promos.id',
                    'promos.name',
                    'assets.path',
                );
        }
        if ($request->has('filter')) {
            foreach ($request->filter as $key => $value) {
                $promo = $promo->where($key, $value);
            }
        }
        if ($request->has('sorting')) {
            foreach ($request->sorting as $key => $value) {
                $promo = $promo->orderBy($key, $value)
                    ->paginate($request->per_page);
            }
        } else {
            $promo = $promo->orderBy('name', 'ASC')
                ->paginate($request->per_page);
        }
        return response()->json($promo);
    }

    public function newestPromo(Request $request) {
        $newestPromo = Promo::join('assets', 'promos.id', '=', 'assets.promo_id')
            ->select(
                'promos.name',
                'promos.id',
                'assets.path',
            )
            ->orderBy('promos.created_at', 'DESC')
            ->take(10)
            ->get();

        return response()->json($newestPromo);
    }

    public function recommendation(Request $request) {
        $recommendation = Promo::join('assets', 'promos.id', '=', 'assets.promo_id')
            ->join('brands', 'brands.id', '=', 'promos.brand_id')
            ->joinSub(function ($query) {
                $query->select(
                    'promo_id',
                    DB::raw('COUNT(*) as wishlist_count'),
                )
                    ->from('favorites')
                    ->groupBy('promo_id');
            }, 'wish_counts', function ($join) {
                $join->on('promos.id', '=', 'wish_counts.promo_id');
            })
            ->select(
                'promos.name',
                'promos.id',
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
    public function promoDetail($id) {
        $user = Auth::user();
        $promo = Promo::join('assets', 'promos.id', '=', 'assets.promo_id')
            ->leftJoin(
                DB::raw('(SELECT promo_id, COUNT(*) as favorite_count FROM favorites GROUP BY promo_id) AS counts'),
                'promos.id',
                '=',
                'counts.promo_id'
            )
            ->select(
                'promos.*',
                'assets.path',
                DB::raw('COALESCE(counts.favorite_count, 0) as favorite_count'),
            )
            ->where('promos.id', $id)
            ->first();

        $brandInfo = Brand::join('promos', 'promos.brand_id', '=', 'brands.id')
            ->join('users', 'users.id', '=', 'brands.user_id')
            ->select(
                'users.mobile',
                'brands.*',
            )
            ->where('brands.id', $promo->brand_id)
            ->groupBy('brands.id', 'users.mobile')
            ->first();

        $isLike = Favorite::where('promo_id', $id)
            ->where('user_id', $user->id)
            ->first();

        return response()->json([
            'promo' => $promo,
            'isLike' => $isLike ? true : false,
            'brandInfo' => $brandInfo,
        ]);
    }

    public function likedPromo($id){
        $user = Auth::user();
        $isLike = Favorite::where('promo_id', $id)
            ->where('user_id', $user->id)
            ->first();

        return response()->json([
            'isLike' => $isLike ? true : false,
        ]);
    }

}
