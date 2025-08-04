<?php
/**
 * Simple test script for the streaming download functionality
 */

// Set up the request
$url = 'http://localhost:8000/api/documents/download-cors';
$data = [
    'file_name' => 'signed_documents/dummy-copy-1753932488457-380572-688ae2c8a422a9.64108218.pdf'
];

echo "Testing streaming download with file: {$data['file_name']}\n\n";

// Set up cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: */*',
    'Origin: http://localhost:3000' // Simulate request from frontend
]);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

// Close cURL session
curl_close($ch);

// Output results
echo "HTTP Status Code: $httpCode\n\n";
echo "Response Headers:\n";
$headerLines = explode("\n", $headers);
foreach ($headerLines as $header) {
    if (trim($header)) {
        echo trim($header) . "\n";
    }
}

echo "\nChecking for important headers:\n";

// Check Content-Disposition header
if (preg_match('/Content-Disposition: attachment/', $headers)) {
    echo "✓ Content-Disposition header is present and set to 'attachment'\n";
} else {
    echo "✗ Content-Disposition header is missing or not set to 'attachment'\n";
}

// Check Content-Type header
if (preg_match('/Content-Type: (.+)/', $headers, $matches)) {
    echo "✓ Content-Type header is present: " . $matches[1] . "\n";
} else {
    echo "✗ Content-Type header is missing\n";
}

// Check Access-Control-Allow-Origin header
if (preg_match('/Access-Control-Allow-Origin: (.+)/', $headers, $matches)) {
    echo "✓ Access-Control-Allow-Origin header is present: " . $matches[1] . "\n";
} else {
    echo "✗ Access-Control-Allow-Origin header is missing\n";
}

// Check Access-Control-Expose-Headers header
if (preg_match('/Access-Control-Expose-Headers: (.+)/', $headers, $matches)) {
    echo "✓ Access-Control-Expose-Headers header is present: " . $matches[1] . "\n";
} else {
    echo "✗ Access-Control-Expose-Headers header is missing\n";
}

// Check if content is binary (PDF)
$isBinary = false;
for ($i = 0; $i < min(strlen($body), 100); $i++) {
    if (ord($body[$i]) < 32 && !in_array(ord($body[$i]), [9, 10, 13])) {
        $isBinary = true;
        break;
    }
}

echo "\nChecking content:\n";
if ($isBinary) {
    echo "✓ Content appears to be binary data (likely the actual file)\n";

    // Check for PDF signature
    if (substr($body, 0, 4) === '%PDF') {
        echo "✓ Content starts with PDF signature (%PDF)\n";

        // Save the file for manual verification
        $outputFile = 'downloaded_test.pdf';
        file_put_contents($outputFile, $body);
        echo "✓ File saved as $outputFile for manual verification\n";
    } else {
        echo "✗ Content does not start with PDF signature\n";

        // Save the first 100 bytes for inspection
        file_put_contents('download_first_100_bytes.bin', substr($body, 0, 100));
        echo "First 100 bytes saved to download_first_100_bytes.bin for inspection\n";
    }
} else {
    echo "✗ Content appears to be text (possibly HTML or an error)\n";

    // Display the first 500 characters of the content
    echo "First 500 characters of content:\n";
    echo substr($body, 0, 500) . "...\n";
}

echo "\nTest completed.\n";
