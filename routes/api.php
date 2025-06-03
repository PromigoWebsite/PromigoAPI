<?php

use App\Http\Controllers\adminPromoController;
use App\Http\Controllers\AuthenticateController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Testpertama;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\BrandPromoController;
use App\Http\Controllers\CloudinaryController;
use App\Http\Controllers\DriveController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SellerRequestController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/register',[AuthenticateController::class,'register']);
Route::post('/login',[AuthenticateController::class,'login']);
Route::middleware('auth:sanctum')->post('/logout',[AuthenticateController::class,'logout']);
Route::middleware('auth:sanctum')->get('/user', [AuthenticateController::class, 'user']);

//Promo
Route::get('/promos',[PromoController::class,'items']);
Route::prefix('/promo')->group(function(){
    Route::get('/recommendation',[PromoController::class,'recommendation']);
    Route::get('/newest',[PromoController::class,'newestPromo']);
    Route::prefix('/{id}')->group(function () {
        Route::get('liked', [PromoController::class, 'likedPromo']);
        Route::get('/', [PromoController::class, 'promoDetail']);
    });
    
});

//Admin controller List
Route::prefix('/admin')->group(function(){
    Route::middleware(['auth:sanctum', 'role:Admin'])->get('/list',[adminPromoController::class,'items']);
    Route::middleware('auth:sanctum')->post('/add', [adminPromoController::class, 'addPromo']);
    Route::middleware('auth:sanctum')->prefix('/{id}')->group(function () {
        Route::post('/edit', [adminPromoController::class, 'editPromo']);
        Route::delete('/delete', [adminPromoController::class, 'deletePromo']);
    });
   
});

//Seller Controller list
Route::middleware(['auth:sanctum', 'role:Seller'])->prefix('/seller')->group(function () {
    Route::get('/list/{id}', [BrandPromoController::class, 'items']);
    Route::delete('/delete/{id}', [BrandPromoController::class, 'deletePromo']);
});

//Seller Request
Route::middleware(['auth:sanctum', 'role:Admin'])->prefix('/request')->group(function () {
    Route::get('/list', [SellerRequestController::class, 'items']);
    Route::prefix('/{id}')->group(function () {
        Route::delete('/delete', [SellerRequestController::class, 'deleteRequest']);
        Route::post('/accept', [SellerRequestController::class, 'acceptRequest']);
    });
});

//Favorite
Route::middleware('auth:sanctum')->prefix('/favorite')->group(function(){
    Route::get('/list', [FavoriteController::class, 'list']);
    Route::delete('/delete/{id}', [FavoriteController::class, 'removeFavorite']);
    Route::put('/add/{id}', [FavoriteController::class, 'addFavorite']);
});

//Profile
Route::middleware('auth:sanctum')->prefix('/profiles')->group(function () {
    Route::post('/edit/{id}', [ProfileController::class, 'editProfileById']);
});
    
// Brand
Route::get('/brands',[BrandController::class,'items']);
Route::prefix('/brand')->group(function () {
    Route::post('/add', [BrandController::class, 'addBrand']);
    Route::prefix('/{id}')->group(function () {
        Route::delete('/delete', [BrandController::class, 'deleteById']);
        Route::get('/', [BrandController::class, 'getBrandById']);
        Route::post('/edit', [BrandController::class, 'editBrandById']);
        Route::get('/promo', [BrandController::class, 'getRelatedPromo']);
    });
});

//cloud
Route::prefix('/drive')->group(function(){
    Route::delete('/delete',[CloudinaryController::class,'fileDelete']);
    Route::get('/exist',[CloudinaryController::class,'fileExist']);
    Route::post('/upload',[DriveController::class,'fileUpload']);
});

//Report
Route::prefix('/report')->group(function(){
    Route::prefix('/{id}')->group(function () {
        Route::post('/', [ReportController::class, 'addReport']);
        Route::delete('/delete', [ReportController::class, 'deleteReport']);
    });    
    Route::get('/list',[ReportController::class,'items']);
});