<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DetectionController;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::group(['middleware' => 'auth:api'], function() {
  Route::resource('detections', DetectionController::class);
  Route::get('maps/{latitude?}/{longitude?}/{radius?}', [DetectionController::class, 'pothole_maps']);
});
