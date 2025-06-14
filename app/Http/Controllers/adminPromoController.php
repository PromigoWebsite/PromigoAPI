<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Brand;
use App\Models\Promo;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Str;

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
        // dd($request->all());
        if ($request->has('page') && $request->page === "all") {
            $promo = $this->baseQuery();
            $promo = $promo->orderByDesc('created_at')
                ->get();
        } else {
            //SEARCH
            $promo = $this->baseQuery();
            $sortingValid = false;
            if ($request->has('search') && $request->search) {
                $promo = $promo->whereRaw('LOWER(promos.name) LIKE ?', ['%' . strtolower($request->search) . '%']);
            }
            if ($request->has('filter') && $request->filter) {
                foreach ($request->filter as $filter => $value) {
                    if ($value === "default") {
                        continue;
                    }
                    switch ($filter) {
                        case 'brand_name':
                            $promo->where('brands.name', $value);
                            break;
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
                    if($value === "default"){
                        continue;
                    }
                    $promo->orderBy($filter, $value);
                    $sortingValid = true;
                }
                if($sortingValid === false){
                    $promo->orderByDesc('created_at');
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
            $existingAsset = Asset::where('promo_id', $id)->first();

            if ($existingAsset && $existingAsset->path) {
                Storage::disk('supabase')->delete($existingAsset->path);
                $existingAsset->delete();
            }

            $deletedPromo = Promo::findOrFail($id)->delete();
            DB::commit();
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json($deletedPromo);
    }

    public function addPromo(Request $request) {
        // dd($request->all());
        try {
            DB::beginTransaction();
            $validated = $request->validate([
                'promoName' => 'required|string',
                'promoType' => 'required|string',
                'description' => 'required|string',
                'promoCategory' => 'required|string',
                'status' => 'required|string',
                'terms' => 'required|string',
                'Media' => 'required|file|mimes:jpeg,png,jpg,gif',
                'started_at' => 'nullable|string',
                'ended_at' => 'nullable|string',
                'brand_id' => 'required|exists:brands,id',
            ], [
                'promoName.required' => 'Nama promo wajib diisi',
                'promoName.string' => 'Nama promo harus berupa teks',

                'promoType.required' => 'Tipe promo wajib diisi',
                'promoType.string' => 'Tipe promo harus berupa teks',

                'description.required' => 'Deskripsi promo wajib diisi',
                'description.string' => 'Deskripsi promo harus berupa teks',

                'promoCategory.required' => 'Kategori promo wajib diisi',
                'promoCategory.string' => 'Kategori promo harus berupa teks',

                'status.required' => 'Status promo wajib diisi',
                'status.string' => 'Status promo harus berupa teks',

                'terms.required' => 'Syarat dan ketentuan promo wajib diisi',
                'terms.string' => 'Syarat dan ketentuan promo harus berupa teks',

                'Media.required' => 'Gambar promo wajib diunggah',
                'Media.file' => 'Media harus berupa file',
                'Media.mimes' => 'Format gambar hanya boleh jpeg, png, jpg, atau gif',

                'started_at.string' => 'Tanggal mulai harus berupa teks',
                'ended_at.string' => 'Tanggal berakhir harus berupa teks',

                'brand_id.required' => 'ID brand wajib diisi',
                'brand_id.exists' => 'Brand yang dipilih tidak ditemukan dalam database',
            ]);

            $promo = [
                'brand_id' => $validated['brand_id'],
                'name' => $validated['promoName'],
                'discount' => null,
                'description' => $validated['description'],
                'category' => $validated['promoCategory'],
                'status' => $validated['status'],
                'terms' => $validated['terms'],
                'type' => $validated['promoType'],
                'started_at' => $validated['started_at'] ?? Carbon::now('UTC'),
                'ended_at' => $validated['ended_at'] ?? Carbon::now('UTC'),
            ];

            $promoId = DB::table('promos')->insertGetId($promo);

            if ($request->hasFile('Media')) {
                $uuid = Str::uuid()->toString();
                $filePath = Storage::disk('supabase')->putFileAs(
                    '/asset/promos',
                    $request->file('Media'),
                    $promoId . '_' . $uuid
                );

                // Create asset record
                DB::table('assets')->insert([
                    'promo_id' => $promoId,
                    'path' => $filePath,
                    'file_name' =>  $promoId . '_' . $uuid,
                    'mime_type' => $request->file('Media')->getMimeType(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Promo added successfully'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function editPromo(Request $request, $id) {
        // dd($request->all());
        try {
            DB::beginTransaction();
            $validated = $request->validate([
                'promoName' => 'required|string',
                'promoType' => 'required|string',
                'description' => 'required|string',
                'promoCategory' => 'required|string',
                'status' => 'required|string',
                'terms' => 'required|string',
                'Media' => 'required|file|mimes:jpeg,png,jpg,gif',
                'started_at' => 'nullable|string',
                'ended_at' => 'nullable|string',
                'brand_id' => 'required|exists:brands,id',
            ], [
                'promoName.required' => 'Nama promo wajib diisi',
                'promoName.string' => 'Nama promo harus berupa teks',

                'promoType.required' => 'Tipe promo wajib diisi',
                'promoType.string' => 'Tipe promo harus berupa teks',

                'description.required' => 'Deskripsi promo wajib diisi',
                'description.string' => 'Deskripsi promo harus berupa teks',

                'promoCategory.required' => 'Kategori promo wajib diisi',
                'promoCategory.string' => 'Kategori promo harus berupa teks',

                'status.required' => 'Status promo wajib diisi',
                'status.string' => 'Status promo harus berupa teks',

                'terms.required' => 'Syarat dan ketentuan promo wajib diisi',
                'terms.string' => 'Syarat dan ketentuan promo harus berupa teks',

                'Media.required' => 'Gambar promo wajib diunggah',
                'Media.file' => 'Media harus berupa file',
                'Media.mimes' => 'Format gambar hanya boleh jpeg, png, jpg, atau gif',

                'started_at.string' => 'Tanggal mulai harus berupa teks',
                'ended_at.string' => 'Tanggal berakhir harus berupa teks',

                'brand_id.required' => 'ID brand wajib diisi',
                'brand_id.exists' => 'Brand yang dipilih tidak ditemukan dalam database',
            ]);

            $promo = [
                'brand_id' => $validated['brand_id'],
                'name' => $validated['promoName'],
                'discount' => null,
                'description' => $validated['description'],
                'category' => $validated['promoCategory'],
                'status' => $validated['status'],
                'terms' => $validated['terms'],
                'type' => $validated['promoType'],
                'started_at' => $validated['started_at'] ?? Carbon::now('UTC'),
                'ended_at' => $validated['ended_at'] ?? Carbon::now('UTC'),
            ];

            $existingPromo = Promo::findOrFail($id);
            $existingAsset = Asset::where('promo_id', $id)->first();

            $uploadedFile = $request->file('Media');
            $uploadedSize = $uploadedFile->getSize();
            $uploadedMime = $uploadedFile->getMimeType();
            

            $existingSize = Storage::disk('supabase')->size($existingAsset->path);
            $existingMime = $existingAsset->mime_type;


            $isSameFile = ($uploadedSize === $existingSize && $uploadedMime === $existingMime);

            if ($existingAsset && $existingAsset->path && !$isSameFile) {
                Storage::disk('supabase')->delete($existingAsset->path);
                $existingAsset->delete();
            }

            $existingPromo->update($promo);

            $promoId = $existingPromo->id;

            if ($request->hasFile('Media') && !$isSameFile) {
                $uuid = Str::uuid()->toString();
                $filePath = Storage::disk('supabase')->putFileAs(
                    '/asset/promos',
                    $request->file('Media'),
                    $promoId . '_' . $uuid
                );

                DB::table('assets')->insert([
                    'promo_id' => $promoId,
                    'path' => $filePath,
                    'file_name' =>  $promoId . '_' . $uuid,
                    'mime_type' => $request->file('Media')->getMimeType(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            return response()->json([
                'message' => 'Promo updated successfully'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
