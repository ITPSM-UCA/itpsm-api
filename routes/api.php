<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\CareerController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\PrerequisiteController;
use App\Http\Controllers\CurriculumSubjectController;
use App\Http\Controllers\ScholarshipController;
use App\Http\Controllers\StudentCurriculaController;
use App\Http\Controllers\PeriodController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\EvaluationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('login', 'App\Http\Controllers\AuthController@login')->name('login');

Route::post('register', [AuthController::class, 'register'])->name('register');

Route::middleware('auth:api')->group(function() {

    Route::get('logout',  [AuthController::class, 'logout'])->name('logout');

    Route::get('logged-user',  [AuthController::class, 'getLoggedUser'])->name('user');

    Route::apiResource('students', StudentController::class);

    Route::apiResource('teachers', TeacherController::class);

    Route::apiResource('careers', CareerController::class);

    Route::apiResource('subjects', SubjectController::class);

    Route::apiResource('curricula', CurriculumController::class);

    Route::apiResource('prerequisites', PrerequisiteController::class);

    Route::apiResource('curriculum-subjects', CurriculumSubjectController::class);

    Route::apiResource('scholarships', ScholarshipController::class);

    Route::apiResource('student-curricula', StudentCurriculaController::class);

    Route::apiResource('periods', PeriodController::class);

    Route::apiResource('sections', SectionController::class);

    Route::apiResource('enrollments', EnrollmentController::class);

    Route::apiResource('evaluations', EvaluationController::class);

    Route::post('students/create-default-pdf', [StudentController::class, 'createDefaultPdf'])->name('students.create-default-pdf');
});
