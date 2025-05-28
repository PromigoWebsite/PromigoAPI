<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Brand;
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
}
