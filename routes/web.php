<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/user/{email}', [UserController::class, 'getUserByEmail']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/check', [AuthController::class, 'checkJWT']);

Route::get('/', function () {
    return view('welcome');
});



