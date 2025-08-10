<?php

use App\Http\Controllers\TestEmailController;
use App\Http\Controllers\TestMailComponentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

// Routes for testing email templates
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/test-email', [TestEmailController::class, 'showForm'])->name('test.email.form');
    Route::post('/test-email/send', [TestEmailController::class, 'sendTestEmail'])->name('test.email.send');
    Route::post('/test-email/all', [TestEmailController::class, 'testAllTemplates'])->name('test.email.all');

    // Test route for mail component rendering
    Route::get('/test-mail-component', [TestMailComponentController::class, 'testMailComponent'])
        ->name('test.mail.component');
});


require __DIR__.'/auth.php';
