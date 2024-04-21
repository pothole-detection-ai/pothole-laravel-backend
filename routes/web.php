<?php

use App\Http\Controllers\Api\DetectionController;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// migrate and optimize
Route::get('/optimize', function () {
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('optimize');
    $optimizeOutput = Artisan::output();

    return "Optimize command output: $optimizeOutput";
});

Route::get('/migrate', function () {
    Artisan::call('migrate');
    $output = Artisan::output();

    return "Fresh Migration with seed command output: $output";
});

Route::get('/migrate-fresh-seed', function () {
    Artisan::call('migrate:fresh --seed');
    $output = Artisan::output();

    return "Fresh Migration with seed command output: $output";
});

Route::get('/composer-dump-autoload', function () {
    // Run composer dump-autoload
    Composer::dumpAutoloads();
    return "Composer dump-autoload completed!";
});

Route::get('/', function () {
    return view('welcome');
});

Route::get('/pothole_depth_collection_data', [DetectionController::class, 'pothole_depth_collection_data_view']);
