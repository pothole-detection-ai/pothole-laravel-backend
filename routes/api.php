<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OutletController;
use App\Http\Controllers\Api\PriceVariantController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;


Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::group(['middleware' => 'auth:api'], function() {
  Route::get('user', [UserController::class, 'index']);
  Route::resource('outlets', OutletController::class);
  Route::resource('categories', CategoryController::class);
  Route::resource('products', ProductController::class);
  Route::resource('price_variants', PriceVariantController::class);
});