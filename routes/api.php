<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DetectionController;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::get('run-optimize', function () {
    Artisan::call('config:clear');
    Artisan::call('optimize');

    $optimizeOutput = Artisan::output();

    return "Optimize command output: $optimizeOutput";
});

Route::get('run-migrate-fresh-seed', function () {

    Artisan::call('migrate:fresh --seed');
    $output = Artisan::output();

    return "Fresh Migration with seed command output: $output";
});

Route::get('run-storage-link', function () {
    $target_folder = base_path().'/storage/app/public';
    $link_folder = $_SERVER['DOCUMENT_ROOT'].'/storage';
    symlink($target_folder, $link_folder);

    Artisan::call('storage:link');
    $storageLinkOutput = Artisan::output();

    return "Storage link command output: $storageLinkOutput";
});

Route::post('detect', [DetectionController::class, 'detect']);
Route::post('pothole_depth_collection_data', [DetectionController::class, 'pothole_depth_collection_data']); // PUTRI
Route::post('saklar', [DetectionController::class, 'saklar']); // PUTRI
Route::get('get_saklar', [DetectionController::class, 'get_saklar']); // PUTRI


Route::group(['middleware' => 'auth:api'], function() {
  Route::resource('detections', DetectionController::class);
  Route::get('maps/{latitude?}/{longitude?}/{radius?}', [DetectionController::class, 'pothole_maps']);
});
