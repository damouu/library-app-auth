<?php

use App\Http\Controllers\AuthController;
use App\Http\Middleware\EnsureTokenIsPresent;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/getUserProfile', [AuthController::class, 'getUserProfile'])->middleware(EnsureTokenIsPresent::class);
Route::post('/check', [AuthController::class, 'checkJWT'])->middleware(EnsureTokenIsPresent::class);
Route::delete('/user', [AuthController::class, 'deleteUser'])->middleware(EnsureTokenIsPresent::class);
