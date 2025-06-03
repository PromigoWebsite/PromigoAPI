<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Brand;
use App\Models\Favorite;
use App\Models\Promo;
use App\Models\Report;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Storage;
use Str;

class BrandController extends Controller {
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
            if ($request->has('filter') && $request->filter) {
                foreach ($request->filter as $filter => $value) {
                    if ($value === "default") {
                        continue;
                    }
                    $brand->where('category', $value);
                }
            }

            if ($request->has('sorting') && $request->sorting) {
                foreach ($request->sorting as $filter => $value) {
                    if ($value === "default") {
                        continue;
                    }
                    $brand->orderBy($filter, $value);
                }
            }

            $brand = $brand->paginate($request->per_page);
        }
        return response()->json($brand);
    }

    function getBrandById($id) {
        DB::beginTransaction();
        try {
            $brand = Brand::findOrFail($id);
            $user = User::where('id', $brand->user_id)->first();
            DB::commit();
            return response()->json([
                'brand' => $brand,
                'user' => $user,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getRelatedPromo($id) {
        try {
            $promo = Promo::join('assets', 'promos.id', '=', 'assets.promo_id')
                ->join('brands', 'brands.id', '=', 'promos.brand_id')
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
                ->where('brands.id', $id)
                ->get();
            return response()->json($promo);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function editBrandById(Request $request, $id) {
        try {
            // dd($request->all());
            $request->validate(
                [
                    'name' => 'required|string',
                    'address' => 'required|string',
                    'category' => 'required|string',
                    'logo' => 'nullable',
                ],
            );

            // dd($id);
            $brand = Brand::findOrFail($id);

            if ($request->hasFile('logo')) {
                $uuid = Str::uuid()->toString();
                $path = Storage::disk('supabase')->putFileAs('/asset/promo', $request->profile_picture, $id . "_" . $uuid);
                Storage::disk('supabase')->delete($brand->logo);
                $brand->update([
                    'logo' => $path,
                ]);
            }

            $brand->update([
                'name' => $request->name,
                'address' => $request->address,
                'category' => $request->category,
                'updated_at' => Carbon::now('UTC'),
            ]);

            return response()->json([
                'message' => 'Profile updated successfully',
                'profile' => $brand
            ], 200);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function addBrand(Request $request) {
        try {
            // dd($request->all());
            DB::beginTransaction();
            $request->validate(
                [
                    'user_id' => 'required|string',
                    'name' => 'required|string',
                    'address' => 'required|string',
                    'category' => 'required|string',
                    'description' => 'required|string',
                    'logo' => 'nullable',
                ],
            );

            $existingRequest = DB::table('seller_requests')->where('brand_name', $request->name)->first();

            if ($request->hasFile('logo') && $existingRequest == null) {
                $uuid = Str::uuid()->toString();
                $filePath = Storage::disk('supabase')->putFileAs(
                    '/asset/brand',
                    $request->file('logo'),
                    $request->name . '_' . $uuid
                );

                $brand = DB::table('seller_requests')->insertGetId([
                    'user_id' => $request->user_id,
                    'brand_name' => $request->name,
                    'brand_address' => $request->address,
                    'brand_category' => $request->category,
                    'description' =>$request->description,
                    'status' => "Awaiting Approval",
                    'brand_image_path' => $filePath,
                ]);


                DB::table('assets')->insert([
                    'path' => $filePath,
                    'file_name' =>  $brand . '_' . $uuid,
                    'mime_type' => $request->file('logo')->getMimeType(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::commit();
            return response()->json([
                'message' => 'Profile updated successfully',
                'profile' => $brand
            ], 200);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }


    function deleteById($id) {
        DB::beginTransaction();
        try {
            $deletedBrand = Brand::findOrFail($id);
            $promos = Promo::where('brand_id', $id)->get();
            $reports = Report::where('brand_id', $id)->get();

            User::where('id', $deletedBrand->user_id)->update([
                'role_id' => 1,
            ]);
            foreach ($promos as $promo) {
                Favorite::where('promo_id', $promo->id)->delete();
                $promo->delete();
            }
            foreach ($reports as $report) {
                $report->delete();
            }
            $deletedBrand = $deletedBrand->delete();
            DB::commit();
            return response()->json($deletedBrand);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
