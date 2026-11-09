<?php

use App\Http\Controllers\CookingChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoLoginController;
use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');
Route::post('/cooking-chat', CookingChatController::class)->middleware('throttle:10,1')->name('cooking-chat');
Route::post('/demo-login', DemoLoginController::class)->middleware(['guest', 'throttle:6,1'])->name('demo.login');
Route::post('/demo-login/admin', DemoLoginController::class)->defaults('demo_role', 'admin')->middleware(['guest', 'throttle:6,1'])->name('demo.admin');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'admin',
])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/', 'admin.dashboard')->name('dashboard');
});
