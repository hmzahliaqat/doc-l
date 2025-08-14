<?php
// Simple script to test the logo API endpoint

// Set the API URL
$apiUrl = 'http://localhost:8000/api/settings/logo';

// Make the API request
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Display the results
echo "HTTP Status Code: " . $httpCode . "\n\n";
echo "Response:\n";
echo $response . "\n";

// Parse the JSON response
$data = json_decode($response, true);
if ($data) {
    echo "\nParsed Data:\n";
    echo "Logo URL: " . $data['logo_url'] . "\n";
    echo "Alt Text: " . $data['alt_text'] . "\n";
} else {
    echo "\nFailed to parse JSON response.\n";
}
