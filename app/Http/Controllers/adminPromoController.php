<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Brand;
use App\Models\Promo;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class adminPromoController extends Controller {
    static function baseQuery() {
        $promo = Promo::select(
            'promos.id',
            'promos.name as name',
            'promos.type as type',
            'promos.category as category',
            'promos.started_at as started_at',
            'promos.created_at as created_at',
            'promos.ended_at as ended_at',
            'brands.name as brand_name',

        )->join('brands', 'brands.id', '=', 'promos.brand_id');

        return $promo;
    }
    public function items(Request $request) {
        //ALL
        if ($request->has('page') && $request->page === "all") {
            $promo = $this->baseQuery();
            $promo = $promo->orderByDesc('created_at')
                ->get();
        } else {
            //SEARCH
            $promo = $this->baseQuery();
            if ($request->has('search') && $request->search) {
                $promo = $promo->whereRaw('LOWER(promos.name) LIKE ?', ['%' . strtolower($request->search) . '%']);
            }

            if ($request->has('sorting') && $request->sorting) {
                foreach ($request->sorting as $filter => $value) {
                    // if($filter == 'brand'){
                    //     $promo->orderBy("brands.". $filter, $value);
                    // }
                    // $promo->orderBy('promos.'. $filter, $value);
                    $promo->orderBy($filter, $value);
                }
            }

            $promo = $promo->paginate($request->per_page);
        }

        $total_promo = Promo::count();
        $total_brand = Brand::count();

        return response()->json([
            'list' => $promo,
            'total_promo' => $total_promo,
            'total_brand' => $total_brand,
        ]);
    }
    
    public function deletePromo($id){
        DB::beginTransaction();
        try {
            Asset::where('promo_id', $id)->update([
                'promo_id' => null,
            ]);
            $deletedPromo = Promo::findOrFail($id)->delete();
            DB::commit();
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json($deletedPromo);
    }
}
