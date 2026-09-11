<?php

use App\Http\Controllers\Api\AcademicPeriodController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\LearningGroupController;
use App\Http\Controllers\Api\LearningGroupStudentController;
use App\Http\Controllers\Api\PeriodController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Auth\AuthenticatedUserController;
use Illuminate\Support\Facades\Route;

Route::get('/user', AuthenticatedUserController::class)
    ->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/academic-periods', [AcademicPeriodController::class, 'index']);
    Route::get('/academic-periods/{academicPeriod}', [AcademicPeriodController::class, 'show']);
    Route::post('/academic-periods', [AcademicPeriodController::class, 'store']);
    Route::match(
        ['put', 'patch'],
        '/academic-periods/{academicPeriod}',
        [AcademicPeriodController::class, 'update'],
    );

    Route::get('/periods', [PeriodController::class, 'index']);
    Route::get('/periods/{period}', [PeriodController::class, 'show']);
    Route::post('/periods', [PeriodController::class, 'store']);
    Route::match(
        ['put', 'patch'],
        '/periods/{period}',
        [PeriodController::class, 'update'],
    );

    Route::get('/classrooms', [ClassroomController::class, 'index']);
    Route::get('/classrooms/{classroom}', [ClassroomController::class, 'show']);
    Route::post('/classrooms', [ClassroomController::class, 'store']);
    Route::match(
        ['put', 'patch'],
        '/classrooms/{classroom}',
        [ClassroomController::class, 'update'],
    );

    Route::get('/teachers', [TeacherController::class, 'index']);
    Route::get('/teachers/{teacher}', [TeacherController::class, 'show']);
    Route::post('/teachers', [TeacherController::class, 'store']);
    Route::match(
        ['put', 'patch'],
        '/teachers/{teacher}',
        [TeacherController::class, 'update'],
    );

    Route::get('/students', [StudentController::class, 'index']);
    Route::get('/students/{student}', [StudentController::class, 'show']);
    Route::post('/students', [StudentController::class, 'store']);
    Route::match(['put', 'patch'], '/students/{student}', [StudentController::class, 'update']);

    Route::get('/learning-groups', [LearningGroupController::class, 'index']);
    Route::get('/learning-groups/{learningGroup}', [LearningGroupController::class, 'show']);
    Route::post('/learning-groups', [LearningGroupController::class, 'store']);
    Route::match(['put', 'patch'], '/learning-groups/{learningGroup}', [LearningGroupController::class, 'update']);
    Route::get('/learning-groups/{learningGroup}/memberships', [LearningGroupController::class, 'memberships']);
    Route::delete('/learning-groups/{learningGroup}', [LearningGroupController::class, 'destroy']);

    Route::get('/learning-group-memberships', [LearningGroupStudentController::class, 'index']);
    Route::get('/learning-group-memberships/{learningGroupStudent}', [LearningGroupStudentController::class, 'show']);
    Route::post('/learning-group-memberships', [LearningGroupStudentController::class, 'store']);
    Route::match(['put', 'patch'], '/learning-group-memberships/{learningGroupStudent}', [LearningGroupStudentController::class, 'update']);
    Route::delete('/learning-group-memberships/{learningGroupStudent}', [LearningGroupStudentController::class, 'destroy']);

    Route::get('/enrollments', [EnrollmentController::class, 'index']);
    Route::get('/enrollments/{enrollment}', [EnrollmentController::class, 'show']);
    Route::post('/enrollments', [EnrollmentController::class, 'store']);
    Route::match(['put', 'patch'], '/enrollments/{enrollment}', [EnrollmentController::class, 'update']);

});
