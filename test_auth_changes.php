<?php

// This is a simple test script to verify our authentication changes
// Run this script with: php test_auth_changes.php

// Include the autoloader
require __DIR__ . '/vendor/autoload.php';

// Bootstrap the Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test function for email verification error message
function testEmailVerificationErrorMessage() {
    echo "Testing email verification error message...\n";

    // Create a test user without email verification
    $user = new \App\Models\User([
        'name' => 'Test User',
        'email' => 'test_' . time() . '@example.com',
        'password' => \Illuminate\Support\Facades\Hash::make('password'),
    ]);
    $user->save();

    echo "Created test user: " . $user->email . "\n";

    // Try to authenticate with the user
    try {
        $request = new \App\Http\Requests\Auth\LoginRequest();
        $request->merge([
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Reflection to access protected/private methods
        $reflector = new ReflectionClass($request);
        $method = $reflector->getMethod('authenticate');
        $method->setAccessible(true);

        $method->invoke($request);

        echo "ERROR: User was able to authenticate without email verification!\n";
    } catch (\Illuminate\Validation\ValidationException $e) {
        $messages = $e->validator->errors()->messages();
        if (isset($messages['email']) &&
            strpos($messages['email'][0], 'Email verification required') !== false) {
            echo "SUCCESS: Email verification error message is correctly displayed!\n";
            echo "Message: " . $messages['email'][0] . "\n";
        } else {
            echo "ERROR: Unexpected validation error: " . json_encode($messages) . "\n";
        }
    } catch (\Exception $e) {
        echo "ERROR: Unexpected exception: " . $e->getMessage() . "\n";
    }

    // Clean up
    $user->delete();
    echo "Test user deleted.\n";
}

// Test function for registration flow
function testRegistrationFlow() {
    echo "\nTesting registration flow...\n";

    // Create a mock request
    $request = new \Illuminate\Http\Request();
    $request->merge([
        'name' => 'Test User',
        'email' => 'test_reg_' . time() . '@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    // Create controller instance
    $controller = new \App\Http\Controllers\Auth\RegisteredUserController();

    // Call the store method
    $response = $controller->store($request);

    // Check if user was created but not logged in
    $user = \App\Models\User::where('email', $request->email)->first();

    if ($user && !\Illuminate\Support\Facades\Auth::check()) {
        echo "SUCCESS: User was created but not automatically logged in!\n";
    } else if (!$user) {
        echo "ERROR: User was not created!\n";
    } else if (\Illuminate\Support\Facades\Auth::check()) {
        echo "ERROR: User was automatically logged in!\n";
    }

    // Clean up
    if ($user) {
        $user->delete();
        echo "Test user deleted.\n";
    }
}

// Run the tests
echo "=== TESTING AUTHENTICATION CHANGES ===\n";
testEmailVerificationErrorMessage();
testRegistrationFlow();
echo "=== TESTS COMPLETED ===\n";
