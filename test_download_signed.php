<?php
/**
 * Test script to verify the download-signed endpoint
 *
 * This script makes a POST request to the download-signed endpoint
 * and checks the response.
 */

// Set up the request
$url = 'http://localhost:8000/api/documents/download-signed';
$data = [
    'document_id' => 1, // Replace with a valid document ID
];

// Get authentication token (if needed)
// You may need to adjust this based on your authentication system
$token = null;
if (file_exists('token.txt')) {
    $token = trim(file_get_contents('token.txt'));
}

// Set up cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Origin: http://localhost:3000', // Simulate request from frontend
    $token ? "Authorization: Bearer $token" : null,
]);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Output the results
echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "Error: $error\n";
}

if ($httpCode >= 200 && $httpCode < 300) {
    echo "Success! The download-signed endpoint is working correctly.\n";

    // Save the response to a file if it's a PDF
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    if (strpos($contentType, 'application/pdf') !== false) {
        file_put_contents('downloaded_document.pdf', $response);
        echo "Downloaded document saved as 'downloaded_document.pdf'\n";
    } else {
        echo "Response: $response\n";
    }
} else {
    echo "Failed! The download-signed endpoint returned an error.\n";
    echo "Response: $response\n";
}
