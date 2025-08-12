<?php

// Test script for the updated share endpoint

// Set up the API base URL
$baseUrl = 'http://localhost:8000/api';

// Function to make API requests
function makeRequest($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init($url);

    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

// Get authentication token (you'll need to replace these with valid credentials)
echo "Authenticating...\n";
$authResponse = makeRequest($baseUrl . '/login', 'POST', [
    'email' => 'your-email@example.com',
    'password' => 'your-password'
]);

if ($authResponse['code'] !== 200 || !isset($authResponse['response']['token'])) {
    echo "Authentication failed. Please check your credentials.\n";
    exit(1);
}

$token = $authResponse['response']['token'];
echo "Authentication successful.\n";

// Test 1: Share a document with a single employee
echo "\nTest 1: Share a document with a single employee\n";
$shareResponse = makeRequest($baseUrl . '/documents/share', 'POST', [
    'document_id' => 'document-id-123', // Replace with a valid document ID
    'employee_id' => 123 // Replace with a valid employee ID
], $token);

echo "Response code: " . $shareResponse['code'] . "\n";
echo "Response: " . json_encode($shareResponse['response'], JSON_PRETTY_PRINT) . "\n";

// Test 2: Share a document with multiple employees
echo "\nTest 2: Share a document with multiple employees\n";
$shareMultipleResponse = makeRequest($baseUrl . '/documents/share', 'POST', [
    'document_id' => 'document-id-123', // Replace with a valid document ID
    'employee_ids' => [123, 124, 125] // Replace with valid employee IDs
], $token);

echo "Response code: " . $shareMultipleResponse['code'] . "\n";
echo "Response: " . json_encode($shareMultipleResponse['response'], JSON_PRETTY_PRINT) . "\n";

// Test 3: Bulk share multiple documents with multiple employees
echo "\nTest 3: Bulk share multiple documents with multiple employees\n";
$bulkShareResponse = makeRequest($baseUrl . '/documents/bulk-share', 'POST', [
    'document_ids' => ['document-id-123', 'document-id-456', 'document-id-789'], // Replace with valid document IDs
    'employee_ids' => [123, 124] // Replace with valid employee IDs
], $token);

echo "Response code: " . $bulkShareResponse['code'] . "\n";
echo "Response: " . json_encode($bulkShareResponse['response'], JSON_PRETTY_PRINT) . "\n";

echo "\nAll tests completed.\n";
