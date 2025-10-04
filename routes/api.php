<?php

use App\Http\Controllers\AuthController;
use App\Http\Middleware\EnsureBasicIsPresent;
use App\Http\Middleware\EnsureTokenIsPresent;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware(EnsureBasicIsPresent::class);
Route::post('/check', [AuthController::class, 'checkJWT'])->middleware(EnsureTokenIsPresent::class);
