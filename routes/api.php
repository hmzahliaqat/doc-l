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

Route::middleware(['auth:sanctum'])->get('/user/otp-settings', [\App\Http\Controllers\UserController::class, 'getOtpSettings']);
Route::middleware(['auth:sanctum'])->post('/user/otp-settings', [\App\Http\Controllers\UserController::class, 'updateOtpSettings']);

// OTP Request and Verification Routes
Route::post('/user/request-otp', [\App\Http\Controllers\UserController::class, 'requestOtp'])
    ->middleware(['throttle:5,1']); // Limit to 5 requests per minute
Route::post('/user/verify-otp', [\App\Http\Controllers\UserController::class, 'verifyOtp'])
    ->middleware(['throttle:5,1']); // Limit to 5 attempts per minute

// API Routes for Email Verification
Route::middleware('auth:sanctum')->group(function () {
    // Send verification email (POST /email/verification-notification)
    Route::post('/email/verification-notification', [App\Http\Controllers\Auth\EmailVerificationController::class, 'store'])
        ->middleware(['throttle:6,1'])
        ->name('verification.send');

    // Get verification status (GET /email/verification-status)
    Route::get('/email/verification-status', [App\Http\Controllers\Auth\EmailVerificationController::class, 'show'])
        ->name('verification.status');
});





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
    Route::get('/external/{id}', 'externalDoc');
    Route::post('/download-signed/', 'download');
    // Add a specific route for cross-origin downloads that doesn't require CSRF
    Route::middleware('cors')->post('/download-cors/', 'downloadCors');
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

Route::middleware(['auth:sanctum'])
    ->group(function () {
        // Email Templates Routes
        Route::apiResource('email-templates', \App\Http\Controllers\API\EmailTemplateController::class);
        Route::post('email-templates/{emailTemplate}/preview', [\App\Http\Controllers\API\EmailTemplateController::class, 'preview']);

        // Subscription Routes for authenticated users
        Route::controller(\App\Http\Controllers\SubscriptionController::class)
            ->prefix('subscriptions')
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/subscribe', 'subscribe');
                Route::put('/swap', 'swap');
                Route::post('/cancel', 'cancel');
                Route::post('/resume', 'resume');
                Route::put('/payment-method', 'updatePaymentMethod');
                Route::get('/plans', 'plans');
                Route::get('/stripe-key', 'getStripeKey');
            });
    });

// Routes that don't require super-admin role
Route::middleware(['auth:sanctum'])
    ->prefix('sp')
    ->group(function () {
        // SuperAdmin Settings Routes without role check
        Route::get('/settings', [\App\Http\Controllers\SuperAdminController::class, 'getSettings']);

        // Subscription Plans Routes without role check
        Route::get('/subscription-plans', [\App\Http\Controllers\SubscriptionPlanController::class, 'index']);
        Route::get('/subscription-plans/{id}', [\App\Http\Controllers\SubscriptionPlanController::class, 'show']);
    });

// Routes that require super-admin role
Route::prefix('sp')
    ->middleware(['auth:sanctum', 'role:super-admin'])
    ->group(function () {
        // SuperAdmin Controller Routes
        Route::controller(\App\Http\Controllers\SuperAdminController::class)
            ->group(function () {
                Route::get('/stats', 'getStats');
                Route::get('/company-with-detail', 'companiesDetails');
                Route::get('/superadmins', 'getSuperadmins');
                Route::post('/superadmins', 'createSuperadmin');
                Route::delete('/superadmins/{id}', 'deleteSuperadmin');

                // SuperAdmin Settings Routes that still need super-admin role
                Route::post('/settings', 'updateSettings');
                Route::delete('/settings', 'deleteSettings');
            });

        // Privacy Policy Routes (Super Admin only)
        Route::post('/privacy-policy', [\App\Http\Controllers\PrivacyPolicyController::class, 'update']);

        // Subscription Plans Routes (Super Admin only) except index and show
        Route::controller(\App\Http\Controllers\SubscriptionPlanController::class)
            ->prefix('subscription-plans')
            ->group(function () {
                Route::post('/', 'store');
                Route::put('/{id}', 'update');
                Route::delete('/{id}', 'destroy');
                Route::patch('/{id}/toggle-active', 'toggleActive');
            });
    });

// Test route for SuperAdminSettings integration
Route::get('/test-email-template', [App\Http\Controllers\TestController::class, 'testEmailTemplate']);

// Guest accessible routes
Route::get('/settings/guest', [\App\Http\Controllers\SuperAdminController::class, 'getGuestSettings']);
Route::get('/subscriptions/plans/guest', [\App\Http\Controllers\SubscriptionController::class, 'guestPlans']);
Route::get('/privacy-policy', [\App\Http\Controllers\PrivacyPolicyController::class, 'show']);

// Stripe Webhook
Route::post('/stripe/webhook', [\Laravel\Cashier\Http\Controllers\WebhookController::class, 'handleWebhook']);
