<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EmployeesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum'])->get('/user/role', [\App\Http\Controllers\UserController::class, 'getRole']);





Route:: prefix('documents')->controller(DocumentController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::put('/{id}', 'update');
    Route::post('/share', 'shareDocument');
    Route::post('/remind', 'remindDocument');
    Route::get('/track', 'track');
    Route::delete('/{id}', 'destroy');
    Route::post('/{id}/archive', 'archive');
    Route::post('/{id}/trash', 'trash');
    Route::get('/trash', 'listTrash');
    Route::post('/{id}/restore', 'restore');
    Route::delete('/{id}/force', 'forceDelete');
    Route::get('/archive', 'listArchive');
    Route::get('/signed', 'listSigned');
    Route::get('{shared_document_id}/{document_pdf_id}/{employeeId}/employee-view', 'employeeView');
    Route::get('/download/{path}', 'downloadDocument')->where('path', '.*');
});

Route::prefix('dashboard')->controller(DashboardController::class)->group(function () {
    Route::get('/', 'index');

});


Route::prefix('employees')->controller(EmployeesController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'save');
    Route::delete('/delete', 'delete');
    Route::post('/import', 'import');
});

Route::prefix('partials')->controller(\App\Http\Controllers\SignatureController::class)->group(function () {
    Route::get('/{employeeId}/{type}', 'index');
    Route::post('/', 'store');
});

// User Reports Routes - Only accessible by super-admin
Route::prefix('reports/users')
    ->middleware(['auth:sanctum', 'role:super-admin'])
    ->controller(\App\Http\Controllers\Reports\UserReportController::class)
    ->group(function () {
        Route::get('/registration-trends', 'registrationTrends');
        Route::get('/active-users', 'activeUsers');
        Route::get('/storage-usage', 'storageUsage');
    });

