<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Email Verification Test Script\n";
echo "=============================\n\n";

// 1. Create a test user
echo "1. Creating a test user...\n";

$testEmail = 'test_' . time() . '@example.com';
$testPassword = 'password123';

// Delete the user if it already exists
$existingUser = User::where('email', $testEmail)->first();
if ($existingUser) {
    $existingUser->delete();
    echo "   - Deleted existing user with email: $testEmail\n";
}

// Create a new user
$user = User::create([
    'name' => 'Test User',
    'email' => $testEmail,
    'password' => Hash::make($testPassword),
]);

echo "   - Created user with email: $testEmail\n";

// 2. Login the user
echo "\n2. Logging in the user...\n";
Auth::login($user);
echo "   - User logged in: " . Auth::check() . "\n";

// 3. Check verification status (should be unverified)
echo "\n3. Checking verification status (should be unverified)...\n";
$verificationStatus = $user->hasVerifiedEmail();
echo "   - Email verified: " . ($verificationStatus ? 'Yes' : 'No') . "\n";

// 4. Send verification email
echo "\n4. Sending verification email...\n";
$response = $app->make('Illuminate\Contracts\Http\Kernel')
    ->handle(Request::create('/api/email/verification-notification', 'POST'));

echo "   - Response status: " . $response->getStatusCode() . "\n";
echo "   - Response body: " . $response->getContent() . "\n";

// 5. Generate verification URL
echo "\n5. Generating verification URL...\n";
$verificationUrl = URL::temporarySignedRoute(
    'verification.verify',
    now()->addMinutes(60),
    ['id' => $user->id, 'hash' => sha1($user->email)]
);
echo "   - Verification URL: $verificationUrl\n";

// 6. Visit verification URL
echo "\n6. Visiting verification URL...\n";
$response = $app->make('Illuminate\Contracts\Http\Kernel')
    ->handle(Request::create($verificationUrl, 'GET'));

echo "   - Response status: " . $response->getStatusCode() . "\n";
if ($response->isRedirect()) {
    echo "   - Redirecting to: " . $response->headers->get('Location') . "\n";
}

// 7. Check verification status again (should be verified)
echo "\n7. Checking verification status again (should be verified)...\n";
$user->refresh();
$verificationStatus = $user->hasVerifiedEmail();
echo "   - Email verified: " . ($verificationStatus ? 'Yes' : 'No') . "\n";
echo "   - Verified at: " . $user->email_verified_at . "\n";

// 8. Check verification status API
echo "\n8. Checking verification status API...\n";
$response = $app->make('Illuminate\Contracts\Http\Kernel')
    ->handle(Request::create('/api/email/verification-status', 'GET'));

echo "   - Response status: " . $response->getStatusCode() . "\n";
echo "   - Response body: " . $response->getContent() . "\n";

// 9. Clean up
echo "\n9. Cleaning up...\n";
$user->delete();
echo "   - Deleted test user\n";

echo "\nTest completed!\n";
