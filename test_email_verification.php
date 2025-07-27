<?php

// This is a simple test script to verify that email verification is working
// Run this script with: php test_email_verification.php

// Include the autoloader
require __DIR__ . '/vendor/autoload.php';

// Bootstrap the Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test function
function testEmailVerification() {
    echo "Testing email verification for login...\n";

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
            in_array('You need to verify your email address before logging in.', $messages['email'])) {
            echo "SUCCESS: Email verification is working correctly!\n";
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

// Run the test
testEmailVerification();
