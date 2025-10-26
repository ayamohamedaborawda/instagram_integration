<?php

use App\Http\Controllers\Api\InstagramController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('instagram')->group(function () {
    Route::get('/webhook', [InstagramController::class, 'verify']);
    Route::post('/webhook', [InstagramController::class, 'handle']);
    Route::get('/callback', [InstagramController::class, 'callback']);

});
