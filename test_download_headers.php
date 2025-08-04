<?php

// Test script to verify download headers and content
// This script simulates a request to the download-signed endpoint

$url = 'http://localhost:8000/api/documents/download-signed';
$data = ['file_name' => 'signed_documents/dummy-copy-1753932488457-380572-688ae2c8a422a9.64108218.pdf']; // Using an actual file from storage
// Set the Content-Type header to application/json for proper JSON handling
$jsonData = json_encode($data);

echo "Testing download functionality with file: {$data['file_name']}\n\n";

// First, test just the headers
echo "=== TESTING HEADERS ===\n";

// Initialize cURL session for headers only
$ch = curl_init($url);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true); // We only want headers, not the body
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: */*',
    'Content-Type: application/json',
    'Origin: http://localhost:3000' // Simulate request from frontend
]);

// Execute cURL session
$response = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
    echo 'cURL error: ' . curl_error($ch) . "\n";
    exit(1);
}

// Get HTTP status code
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "HTTP Status Code: $httpCode\n\n";

// Close cURL session
curl_close($ch);

// Display headers
echo "Response Headers:\n";
$headers = explode("\n", $response);
foreach ($headers as $header) {
    if (trim($header)) {
        echo trim($header) . "\n";
    }
}

echo "\n";
echo "Checking for Content-Disposition header...\n";
if (strpos($response, 'Content-Disposition: attachment') !== false) {
    echo "✓ Content-Disposition header is present and set to 'attachment'\n";
} else {
    echo "✗ Content-Disposition header is missing or not set to 'attachment'\n";
}

echo "\nChecking for Content-Type header...\n";
if (preg_match('/Content-Type: (.+)/', $response, $matches)) {
    echo "✓ Content-Type header is present: " . $matches[1] . "\n";
} else {
    echo "✗ Content-Type header is missing\n";
}

// Now test downloading a small portion of the file
echo "\n\n=== TESTING CONTENT ===\n";

// Initialize cURL session for content
$ch = curl_init($url);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: */*',
    'Content-Type: application/json',
    'Origin: http://localhost:3000' // Simulate request from frontend
]);
curl_setopt($ch, CURLOPT_RANGE, '0-1023'); // Get only the first 1KB of the file

// Execute cURL session
$content = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
    echo 'cURL error: ' . curl_error($ch) . "\n";
    exit(1);
}

// Get HTTP status code and content type
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

echo "HTTP Status Code: $httpCode\n";
echo "Content Type: $contentType\n";
echo "Content Length: " . strlen($content) . " bytes\n\n";

// Check if content is binary (PDF) or text (HTML)
$isBinary = false;
for ($i = 0; $i < min(strlen($content), 100); $i++) {
    if (ord($content[$i]) < 32 && !in_array(ord($content[$i]), [9, 10, 13])) {
        $isBinary = true;
        break;
    }
}

if ($isBinary) {
    echo "✓ Content appears to be binary data (likely the actual file)\n";

    // Check for PDF signature
    if (substr($content, 0, 4) === '%PDF') {
        echo "✓ Content starts with PDF signature (%PDF)\n";
    } else {
        echo "✗ Content does not start with PDF signature\n";
    }
} else {
    echo "✗ Content appears to be text (possibly HTML)\n";

    // Display the first 200 characters of the content
    echo "First 200 characters of content:\n";
    echo substr($content, 0, 200) . "...\n";
}

// Close cURL session
curl_close($ch);

echo "\nTest completed.\n";
