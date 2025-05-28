<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Brand;
use App\Models\Favorite;
use App\Models\Promo;
use App\Models\Report;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class BrandController extends Controller
{
    public function items(Request $request) {
        //ALL
        if ($request->has('page') && $request->page === "all") {
            $brand = Brand::get();
        } else {
            //SEARCH
            $brand = Brand::query();
            if ($request->has('search') && $request->search) {
                $brand = $brand->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($request->search) . '%']);
            }

            if ($request->has('sorting') && $request->sorting) {
                foreach ($request->sorting as $filter => $value) {
                    // if($filter == 'brand'){
                    //     $promo->orderBy("brands.". $filter, $value);
                    // }
                    // $promo->orderBy('promos.'. $filter, $value);
                    $brand->orderBy($filter, $value);
                }
            }

            $brand = $brand->paginate($request->per_page);
        }
        return response()->json($brand);
    }
    function deleteById($id){
        DB::beginTransaction();
        try {
            $promos = Promo::where('brand_id', $id)->get();
            $reports = Promo::where('brand_id', $id)->get();
            foreach ($promos as $promo) {
                Favorite::where('promo_id', $promo->id)->delete();
                $promo->delete();
            }
            foreach ($reports as $report) {
                $report->delete();
            }
            $deletedBrand = Brand::findOrFail($id)->delete();
            DB::commit();
            return response()->json($deletedBrand);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
