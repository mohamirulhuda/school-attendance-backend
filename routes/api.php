<?php

use App\Http\Controllers\Auth\AuthenticatedUserController;
use Illuminate\Support\Facades\Route;

Route::get('/user', AuthenticatedUserController::class)
    ->middleware('auth:sanctum');
