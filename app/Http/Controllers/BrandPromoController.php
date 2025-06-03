<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Promo;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BrandPromoController extends Controller {
    static function baseQuery() {
        $promo = Promo::select(
            'promos.id',
            'promos.name as name',
            'promos.type as type',
            'promos.category as category',
            'promos.started_at as started_at',
            'promos.created_at as created_at',
            'promos.ended_at as ended_at',

        )->join('brands', 'brands.id', '=', 'promos.brand_id');

        return $promo;
    }
    public function items(Request $request, $id) {
        // dd($request->all());
        //ALL
        if ($request->has('page') && $request->page === 0) {
            $promo = $this->baseQuery();
            $promo = $promo->where('brands.id', $id)
                ->orderByDesc('created_at')
                ->get();
        } else {
            //SEARCH
            $promo = $this->baseQuery()->where('brands.id', $id);
            if ($request->has('search') && $request->search) {
                $promo = $promo->whereRaw('LOWER(promos.name) LIKE ?', ['%' . strtolower($request->search) . '%']);
            }
            if ($request->has('filter') && $request->filter) {
                foreach ($request->filter as $filter => $value) {
                    if ($value === "default") {
                        continue;
                    }
                    switch ($filter) {
                        case 'type':
                            $promo->where('promos.type', $value);
                            break;
                        case 'category':
                            $promo->where('promos.category', $value);
                            break;
                    }
                }
            }

            if ($request->has('sorting') && $request->sorting) {
                foreach ($request->sorting as $filter => $value) {
                    if ($value === "default") {
                        continue;
                    }
                    $promo->orderBy($filter, $value);
                }
            }

            $promo = $promo->paginate($request->per_page);
        }

        $total_promo = Promo::join('brands', 'brands.id', '=', 'promos.brand_id')->where('brands.id', $id)->count();

        return response()->json([
            'list' => $promo,
            'total_promo' => $total_promo,
        ]);
    }

    public function deletePromo($id) {
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
