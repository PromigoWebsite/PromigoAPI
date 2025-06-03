<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use DB;
use Exception;
use Illuminate\Http\Request;

class SellerRequestController extends Controller {
    private static function baseQuery() {
        $sellerRequest = DB::table('seller_requests as sr')
            ->join('users', 'users.id', '=', 'sr.user_id')
            ->select(
                'sr.id as id',
                'users.username as name',
                'users.mobile as mobile',
                'sr.brand_name as brand_name',
                'sr.brand_address as brand_address',
                'sr.brand_image_path as brand_image_path',
                'sr.brand_category as brand_category',
                'sr.created_at as created_at',
            );

        return $sellerRequest;
    }
    public function items(Request $request) {
        //ALL
        if ($request->has('page') && $request->page === "all") {
            $sellerRequest = $this->baseQuery()->get();
        } else {
            //SEARCH
            $sellerRequest = $this->baseQuery();
            if ($request->has('search') && $request->search) {
                $sellerRequest = $sellerRequest->whereRaw('LOWER(sr.brand_name) LIKE ?', ['%' . strtolower($request->search) . '%']);
            }

            if ($request->has('sorting') && $request->sorting) {
                foreach ($request->sorting as $filter => $value) {
                    if ($value === "default") {
                        continue;
                    }
                    $sellerRequest->orderBy($filter, $value);
                }
            }

            $sellerRequest = $sellerRequest->paginate($request->per_page);
        }
        return response()->json($sellerRequest);
    }

    public function deleteRequest($id) {
        try {
            $deletedRequest = DB::table('seller_requests as sr')
                ->where('id', $id)
                ->delete();
            return response()->json($deletedRequest);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function acceptRequest($id) {
        DB::beginTransaction();
        try {
            $sellerRequest = DB::table('seller_requests as sr')
                ->where('id', $id)
                ->first();

            if (!$sellerRequest) {
                return response()->json(['message' => 'Request not found'], 404);
            }

            $brand = Brand::create([
                'user_id' => $sellerRequest->user_id,
                'description' => $sellerRequest->description,
                'name' => $sellerRequest->brand_name,
                'address' => $sellerRequest->brand_address,
                'logo' => $sellerRequest->brand_image_path,
                'category' => $sellerRequest->brand_category,
            ]);

            DB::table('users')
                ->where('id', $sellerRequest->user_id)
                ->update(['role_id' => 2]);

            DB::table('seller_requests')
                ->where('id', $id)
                ->delete();

            DB::commit();

            return response()->json([
                'message' => 'Seller request accepted successfully',
                'brand' => $brand
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
