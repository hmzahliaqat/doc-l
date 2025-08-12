<?php

/**
 * Test script for the view-document redirect functionality
 *
 * This script tests the redirect from the Laravel backend to the Vue.js frontend
 * for the /view-document route with query parameters.
 */

// Configuration
$backendUrl = 'http://localhost:8000'; // Change to your backend URL
$sharedDocumentId = 1; // Replace with a valid shared document ID
$documentPdfId = 'doc-123'; // Replace with a valid document PDF ID
$employeeId = 123; // Replace with a valid employee ID

// Function to make HTTP requests and follow redirects
function makeRequest($url, $followRedirects = true, $maxRedirects = 5) {
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);

    if ($followRedirects) {
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, $maxRedirects);
    } else {
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $redirectCount = curl_getinfo($ch, CURLINFO_REDIRECT_COUNT);
    $redirectUrl = null;

    // Extract redirect URL if there is one
    if (!$followRedirects && ($httpCode == 301 || $httpCode == 302 || $httpCode == 303 || $httpCode == 307 || $httpCode == 308)) {
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headerSize);
        $matches = [];
        preg_match('/Location:(.*?)\n/', $header, $matches);
        $redirectUrl = trim($matches[1]);
    }

    curl_close($ch);

    return [
        'code' => $httpCode,
        'response' => $response,
        'effective_url' => $effectiveUrl,
        'redirect_count' => $redirectCount,
        'redirect_url' => $redirectUrl
    ];
}

// Test 1: Check if the backend redirects to the frontend
echo "Test 1: Check if the backend redirects to the frontend\n";
$url = "{$backendUrl}/api/documents/{$sharedDocumentId}/{$documentPdfId}/{$employeeId}/employee-view";
$response = makeRequest($url, false);

echo "Response code: " . $response['code'] . "\n";
if ($response['redirect_url']) {
    echo "Redirect URL: " . $response['redirect_url'] . "\n";

    // Parse the redirect URL to check if it contains the correct query parameters
    $parsedUrl = parse_url($response['redirect_url']);
    $queryParams = [];
    if (isset($parsedUrl['query'])) {
        parse_str($parsedUrl['query'], $queryParams);
    }

    echo "Path: " . $parsedUrl['path'] . "\n";
    echo "Query parameters:\n";
    foreach ($queryParams as $key => $value) {
        echo "  {$key}: {$value}\n";
    }

    // Check if the path is correct (case-insensitive comparison)
    if (strtolower($parsedUrl['path']) === '/view-document') {
        echo "✅ Path is correct\n";
    } else {
        echo "❌ Path is incorrect. Expected: /view-document, Got: " . $parsedUrl['path'] . "\n";
    }

    // Check if all required query parameters are present
    $requiredParams = [
        'shared_document_id' => $sharedDocumentId,
        'document_pdf_id' => $documentPdfId,
        'employee_id' => $employeeId,
        'is_employee' => 'true'
    ];

    $missingParams = [];
    foreach ($requiredParams as $key => $value) {
        if (!isset($queryParams[$key]) || $queryParams[$key] != $value) {
            $missingParams[] = $key;
        }
    }

    if (empty($missingParams)) {
        echo "✅ All required query parameters are present and correct\n";
    } else {
        echo "❌ Missing or incorrect query parameters: " . implode(', ', $missingParams) . "\n";
    }
} else {
    echo "❌ No redirect URL found\n";
}

// Test 2: Follow the redirect and check if the frontend handles it correctly
echo "\nTest 2: Follow the redirect and check if the frontend handles it correctly\n";
$response = makeRequest($url, true);

echo "Response code: " . $response['code'] . "\n";
echo "Effective URL: " . $response['effective_url'] . "\n";
echo "Redirect count: " . $response['redirect_count'] . "\n";

// Check if the final response code is 200 (OK)
if ($response['code'] === 200) {
    echo "✅ Frontend successfully handled the request\n";
} else {
    echo "❌ Frontend returned an error: " . $response['code'] . "\n";
}

echo "\nAll tests completed.\n";
