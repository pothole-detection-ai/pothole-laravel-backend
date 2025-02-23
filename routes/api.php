<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DetectionController;

Route::get('run-optimize', function () {
  Artisan::call('config:clear');
  Artisan::call('optimize');
  $optimizeOutput = Artisan::output();
  return "Optimize command output: $optimizeOutput";
});
  
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('detect', [DetectionController::class, 'detect']);
Route::post('pothole_depth_collection_data', [DetectionController::class, 'pothole_depth_collection_data']); // PUTRI
Route::post('saklar', [DetectionController::class, 'saklar']); // PUTRI
Route::get('get_saklar', [DetectionController::class, 'get_saklar']); // PUTRI

Route::group(['middleware' => 'auth:api'], function() {
  Route::resource('detections', DetectionController::class);
  Route::get('maps/{latitude?}/{longitude?}/{radius?}', [DetectionController::class, 'pothole_maps']);
});
