<?php

use App\Http\Controllers\AuthController;
use App\Http\Middleware\EnsureBasicIsPresent;
use App\Http\Middleware\EnsureTokenIsPresent;
use Illuminate\Support\Facades\Route;


Route::prefix('auth')->group(function () {

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login'])->middleware(EnsureBasicIsPresent::class);

    Route::middleware([EnsureTokenIsPresent::class])->group(function () {
        Route::get('/profile', [AuthController::class, 'getUserProfile']);
        Route::delete('/user', [AuthController::class, 'deleteUser']);
    });
});
