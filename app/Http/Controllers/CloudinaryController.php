<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Api\Upload\UploadApi;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Exception;

class CloudinaryController extends Controller
{
    protected $adminAPI;
    protected $uploadAPI;

    public function __construct()
    {
        $this->adminAPI = Cloudinary::adminApi();
        $this->uploadAPI = Cloudinary::uploadApi();
    }

    public function fileDelete(Request $request){
        try {
            $response = $this->uploadAPI->destroy($request->publicId);
            
            if($response) {
                return response()->json(['message' => 'File deleted successfully'], 200);
            } else {
                return response()->json(['message' => 'Failed to delete file'], 400);
            }
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error deleting file',
                'error' => $e->getMessage()
            ], 500);    
        }
    }

    public function fileUpload(Request $request){
        try {
            
            $filePath = storage_path('app/public/KFCPromotional.jpg');
        
            $response = $this->uploadAPI->upload($filePath,[
                'use_filename'=>true,
            ]); 

            $material = [
                'promo_id' => $request->promo_id,
                'path' => $response['public_id'],
                'mime_type' => $response['resource_type'].'/'.$response['format'],
                'size' => $response['bytes'],
                'file_name' => $response['display_name'],
            ];

            $asset = Asset::create($material);

            if($response) {
                return response()->json(['message' => 'File uploaded successfully'], 200);
            } else {
                return response()->json(['message' => 'Failed to upload file'], 400);
            }
        } catch (Exception $e) {
            return response()->json([
                'message'=>"error",
                'error'=>$e->getMessage(),
            ],500);
        }
    }
}