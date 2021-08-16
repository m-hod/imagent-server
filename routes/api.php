<?php

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:api')->group(function () {
    Route::get('tag/{tag}', [TagController::class, 'index']);
    Route::post('tag', [TagController::class, 'store']);
    Route::put('tag/{tag}', [TagController::class, 'update']);
    Route::delete('tag/{tag}', [TagController::class, 'destroy']);
});
