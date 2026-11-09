<?php

use App\Http\Controllers\CookingChatController;
use App\Http\Controllers\MobileAuthController;
use App\Http\Controllers\MobileKitchenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [MobileAuthController::class, 'store'])->middleware('throttle:5,1');
    Route::post('auth/register', [MobileAuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('auth/forgot-password', [MobileAuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        Route::delete('auth/token', [MobileAuthController::class, 'destroy']);
        Route::get('kitchen', [MobileKitchenController::class, 'index']);
        Route::post('pantry', [MobileKitchenController::class, 'store']);
        Route::put('pantry/{item}', [MobileKitchenController::class, 'update']);
        Route::delete('pantry/{item}', [MobileKitchenController::class, 'destroy']);
        Route::put('equipment', [MobileKitchenController::class, 'equipment']);
    });
});

Route::post('/v1/cooking-chat', CookingChatController::class)
    ->middleware(['auth:sanctum', 'throttle:10,1'])->name('api.cooking-chat');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
