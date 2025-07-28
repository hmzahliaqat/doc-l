<?php

// This is a simple script to test the login functionality
// Run this script from the command line: php test_login.php

// First, we need to get a CSRF token
$csrfUrl = 'http://localhost/pdf-sig/public/csrf-cookie';
$loginUrl = 'http://localhost/pdf-sig/public/login';

// Initialize cURL session for CSRF token
$ch = curl_init($csrfUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt'); // Save cookies to a file
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt'); // Read cookies from a file

// Execute CSRF request
$response = curl_exec($ch);
curl_close($ch);

// Extract XSRF-TOKEN from cookies
$xsrfToken = '';
if (file_exists('cookie.txt')) {
    $cookies = file_get_contents('cookie.txt');
    preg_match('/XSRF-TOKEN\s+(\S+)/', $cookies, $matches);
    if (isset($matches[1])) {
        $xsrfToken = urldecode($matches[1]);
        echo "XSRF Token: " . $xsrfToken . "\n";
    }
}

// Set up the login request
$data = [
    'email' => 'Emarkethosting@gmail.com', // Super admin email from the seeder
    'password' => 'password', // Password from the seeder
];

// Initialize cURL session for login
$ch = curl_init($loginUrl);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/x-www-form-urlencoded',
    'X-XSRF-TOKEN: ' . $xsrfToken, // Include CSRF token in header
]);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt'); // Save cookies to a file
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt'); // Read cookies from a file

// Execute cURL session
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Close cURL session
curl_close($ch);

// Output the results
echo "HTTP Status Code: " . $httpCode . "\n";
echo "Response Body:\n" . $response . "\n";

// If the response is JSON, decode and display it in a more readable format
if ($response && $httpCode == 200) {
    $decodedResponse = json_decode($response, true);
    if ($decodedResponse) {
        echo "Decoded Response:\n";
        print_r($decodedResponse);

        // Check if the key is present for super-admin
        if (isset($decodedResponse['key'])) {
            echo "\nSuccess! The key is present in the response for super-admin user.\n";
        } else {
            echo "\nError: The key is not present in the response.\n";
        }
    }
}
