<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Testpertama;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\BrandController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


//Promo
Route::get('/promos',[PromoController::class,'items']);
Route::prefix('/promo')->group(function(){
    Route::get('/recommendation',[PromoController::class,'recommendation']);
    Route::get('/newest',[PromoController::class,'newestPromo']);
});

// Brand
Route::get('/brands',[BrandController::class,'items']);