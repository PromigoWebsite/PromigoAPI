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
    Route::get('liked/{id}', [PromoController::class, 'likedPromo']);
    Route::get('{id}', [PromoController::class,'promoDetail']);
});

//Admin controller List
Route::middleware(['auth:sanctum', 'role:Admin'])->prefix('/admin')->group(function(){
    Route::get('/list',[adminPromoController::class,'items']);
    Route::delete('/delete/{id}', [adminPromoController::class, 'deletePromo']);
});

//Seller Controller list
Route::middleware(['auth:sanctum', 'role:Seller'])->prefix('/seller')->group(function () {
    Route::get('/list/{id}', [BrandPromoController::class, 'items']);
    Route::delete('/delete/{id}', [BrandPromoController::class, 'deletePromo']);
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

//cloud
Route::prefix('/drive')->group(function(){
    Route::delete('/delete',[CloudinaryController::class,'fileDelete']);
    Route::get('/exist',[CloudinaryController::class,'fileExist']);
    Route::post('/upload',[DriveController::class,'fileUpload']);
});

//Report
Route::prefix('/report')->group(function(){
    Route::post('{id}', [ReportController::class,'addReport']);
    Route::get('/list',[ReportController::class,'items']);
});