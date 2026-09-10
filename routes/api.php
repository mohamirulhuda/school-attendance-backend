<?php

use App\Http\Controllers\Api\AcademicPeriodController;
use App\Http\Controllers\Auth\AuthenticatedUserController;
use Illuminate\Support\Facades\Route;

Route::get('/user', AuthenticatedUserController::class)
    ->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/academic-periods', [AcademicPeriodController::class, 'index']);
    Route::get('/academic-periods/{academicPeriod}', [AcademicPeriodController::class, 'show']);
    Route::post('/academic-periods', [AcademicPeriodController::class, 'store']);
    Route::match(['put', 'patch'], '/academic-periods/{academicPeriod}', [AcademicPeriodController::class, 'update']);
});
