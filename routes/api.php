<?php

use App\Http\Controllers\Api\AcademicPeriodController;
use App\Http\Controllers\Api\PeriodController;
use App\Http\Controllers\Auth\AuthenticatedUserController;
use Illuminate\Support\Facades\Route;

Route::get('/user', AuthenticatedUserController::class)
    ->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/academic-periods', [AcademicPeriodController::class, 'index']);
    Route::get('/academic-periods/{academicPeriod}', [AcademicPeriodController::class, 'show']);
    Route::post('/academic-periods', [AcademicPeriodController::class, 'store']);
    Route::match(['put', 'patch'], '/academic-periods/{academicPeriod}', [AcademicPeriodController::class, 'update']);

    Route::get('/periods', [PeriodController::class, 'index']);
    Route::get('/periods/{period}', [PeriodController::class, 'show']);
    Route::post('/periods', [PeriodController::class, 'store']);
    Route::match(['put', 'patch'], '/periods/{period}', [PeriodController::class, 'update']);
});
