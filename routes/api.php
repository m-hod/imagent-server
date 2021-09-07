<?php

use App\Http\Controllers\ImageController;
use App\Http\Controllers\TagController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['verified', 'auth'])->group(function () {
    Route::get('user/tags', [TagController::class, 'index']);
    Route::post('user/tag', [TagController::class, 'store']);
    Route::delete('user/tag/{tag}', [TagController::class, 'destroy']);

    Route::get('user/images', [ImageController::class, 'index']);
    Route::post('user/image', [ImageController::class, 'store']);
    Route::put('user/image/{image}', [ImageController::class, 'update']);
    Route::delete('user/image/{image}', [ImageController::class, 'delete']);
});
