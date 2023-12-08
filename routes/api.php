<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\OutletController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ProductPriceVariantController;
use App\Http\Controllers\Api\ProductPriceVariantCategoryController;


Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::group(['middleware' => 'auth:api'], function() {
  Route::get('user', [UserController::class, 'index']);
  Route::resource('outlets', OutletController::class);
  Route::resource('product_categories', ProductCategoryController::class);
  Route::resource('products', ProductController::class);
  Route::resource('product_price_variants', ProductPriceVariantController::class);
  Route::resource('product_price_variant_categories', ProductPriceVariantCategoryController::class);
  Route::resource('customers', CustomerController::class);
});
