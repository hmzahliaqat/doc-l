<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EmployeesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::post('/register', [AuthController::class, 'register']);


Route::prefix('documents')->controller(DocumentController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::put('/{id}', 'update');
    Route::delete('/{id}', 'destroy');
    Route::post('/{id}/archive', 'archive');
    Route::post('/{id}/trash', 'trash');
    Route::get('/trash', 'listTrash');
    Route::post('/{id}/restore', 'restore');
    Route::delete('/{id}/force', 'forceDelete');
    Route::get('/archive', 'listArchive');
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