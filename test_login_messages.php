<?php

// This is a simple test script to verify our login error messages
// Run this script with: php test_login_messages.php

// Include the autoloader
require __DIR__ . '/vendor/autoload.php';

// Bootstrap the Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test function for invalid credentials message
function testInvalidCredentialsMessage() {
    echo "Testing invalid credentials message...\n";

    // Create a test request with invalid credentials
    try {
        // Test the AuthController login method
        $controller = new \App\Http\Controllers\AuthController();
        $request = new \Illuminate\Http\Request();
        $request->merge([
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $controller->login($request);

        echo "ERROR: Login succeeded with invalid credentials!\n";
    } catch (\Illuminate\Validation\ValidationException $e) {
        $messages = $e->validator->errors()->messages();
        if (isset($messages['email'])) {
            echo "SUCCESS: Invalid credentials error message is displayed!\n";
            echo "Message: " . $messages['email'][0] . "\n";

            // Check if the message is clear and helpful
            if (strpos($messages['email'][0], 'check your email and password') !== false) {
                echo "SUCCESS: The error message is clear and helpful!\n";
            } else {
                echo "ERROR: The error message is not clear enough!\n";
            }
        } else {
            echo "ERROR: Unexpected validation error: " . json_encode($messages) . "\n";
        }
    } catch (\Exception $e) {
        echo "ERROR: Unexpected exception: " . $e->getMessage() . "\n";
    }
}

// Test function for LoginRequest
function testLoginRequestMessage() {
    echo "\nTesting LoginRequest invalid credentials message...\n";

    try {
        $request = new \App\Http\Requests\Auth\LoginRequest();
        $request->merge([
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        // Reflection to access protected/private methods
        $reflector = new ReflectionClass($request);
        $method = $reflector->getMethod('authenticate');
        $method->setAccessible(true);

        $method->invoke($request);

        echo "ERROR: Authentication succeeded with invalid credentials!\n";
    } catch (\Illuminate\Validation\ValidationException $e) {
        $messages = $e->validator->errors()->messages();
        if (isset($messages['email'])) {
            echo "SUCCESS: Invalid credentials error message is displayed!\n";
            echo "Message: " . $messages['email'][0] . "\n";

            // Check if the message is clear and helpful
            if (strpos($messages['email'][0], 'check your email and password') !== false) {
                echo "SUCCESS: The error message is clear and helpful!\n";
            } else {
                echo "ERROR: The error message is not clear enough!\n";
            }
        } else {
            echo "ERROR: Unexpected validation error: " . json_encode($messages) . "\n";
        }
    } catch (\Exception $e) {
        echo "ERROR: Unexpected exception: " . $e->getMessage() . "\n";
    }
}

// Run the tests
echo "=== TESTING LOGIN ERROR MESSAGES ===\n";
testInvalidCredentialsMessage();
testLoginRequestMessage();
echo "=== TESTS COMPLETED ===\n";
