<?php

// This script checks if there are any SuperAdminSetting records in the database
// and if they have valid Stripe API keys.

// Bootstrap the Laravel application
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Import the SuperAdminSetting model
use App\Models\SuperAdminSetting;

// Check if any SuperAdminSetting records exist
$settings = SuperAdminSetting::first();

if (!$settings) {
    echo "No SuperAdminSetting records found in the database.\n";
    echo "This is likely the cause of the Stripe API key error.\n";

    // Create a new SuperAdminSetting record with placeholder values
    echo "\nWould you like to create a new SuperAdminSetting record with placeholder Stripe keys? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (trim($line) == 'yes') {
        $settings = new SuperAdminSetting();
        $settings->app_name = 'PDF Signature App';
        $settings->stripe_app_key = 'PLACEHOLDER_STRIPE_APP_KEY';
        $settings->stripe_secret_key = 'PLACEHOLDER_STRIPE_SECRET_KEY';
        $settings->save();

        echo "Created a new SuperAdminSetting record with placeholder Stripe keys.\n";
        echo "Please update these keys with valid Stripe API keys using the admin interface.\n";
    }
} else {
    echo "SuperAdminSetting record found.\n";

    // Check if Stripe API keys are set
    if (empty($settings->stripe_app_key)) {
        echo "Warning: stripe_app_key is empty or null.\n";
    } else {
        echo "stripe_app_key is set: " . substr($settings->stripe_app_key, 0, 5) . "...\n";
    }

    if (empty($settings->stripe_secret_key)) {
        echo "Warning: stripe_secret_key is empty or null.\n";
        echo "This is likely the cause of the Stripe API key error.\n";

        // Update the Stripe secret key with a placeholder value
        echo "\nWould you like to update the Stripe secret key with a placeholder value? (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim($line) == 'yes') {
            $settings->stripe_secret_key = 'PLACEHOLDER_STRIPE_SECRET_KEY';
            $settings->save();

            echo "Updated the Stripe secret key with a placeholder value.\n";
            echo "Please update this key with a valid Stripe secret key using the admin interface.\n";
        }
    } else {
        echo "stripe_secret_key is set: " . substr($settings->stripe_secret_key, 0, 5) . "...\n";
    }
}

echo "\nDone.\n";
