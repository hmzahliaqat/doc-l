# Cross-Origin File Download Solution

## Problem Description

The application was experiencing issues with file downloads when accessing the Laravel backend from a Vue.js frontend running on a different origin (localhost:3000). Downloaded files were becoming corrupted, likely due to issues with headers and cross-origin resource sharing (CORS) restrictions.

## Root Causes

1. **CORS Headers**: When downloading files across different origins, browsers enforce strict security policies. The necessary headers were not being exposed to the frontend.

2. **Content Handling**: The way the file content was being served didn't properly handle cross-origin requests, leading to corrupted downloads.

3. **CSRF Protection**: Laravel's CSRF protection was interfering with cross-origin POST requests for file downloads.

## Solution Implemented

### 1. Updated CORS Configuration

Modified the CORS configuration to expose the necessary headers for file downloads:

```php
// config/cors.php
'exposed_headers' => ['Content-Disposition', 'Content-Type', 'Content-Length'],
```

This allows the frontend to access these critical headers when processing the download response.

### 2. Enhanced Download Method

Updated the download method in DocumentController.php to better handle file streaming:

```php
public function download(Request $request)
{
    $path = $request->file_name;
    Log::info('Downloading file: ' . $path);

    if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
        return response()->json(['error' => 'File not found'], 404);
    }

    $file = \Illuminate\Support\Facades\Storage::disk('public')->path($path);
    $filename = basename($path);

    if (!file_exists($file)) {
        return response()->json(['error' => 'File not found on disk'], 404);
    }

    // Get file info
    $fileSize = filesize($file);
    $mimeType = mime_content_type($file);
    
    // Use file() for standard downloads
    return response()->file($file, [
        'Content-Type' => $mimeType,
        'Content-Length' => $fileSize,
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma' => 'no-cache',
        'Expires' => '0'
    ]);
}
```

### 3. Added Cross-Origin Download Method

Created a specialized method for cross-origin downloads with explicit CORS headers:

```php
/**
 * Special method for cross-origin downloads that bypasses CSRF protection
 * and adds explicit CORS headers
 *
 * @param Request $request
 * @return \Symfony\Component\HttpFoundation\StreamedResponse
 */
public function downloadCors(Request $request)
{
    $path = $request->file_name;
    Log::info('Cross-origin downloading file: ' . $path);

    if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
        return response()->json(['error' => 'File not found'], 404);
    }

    $file = \Illuminate\Support\Facades\Storage::disk('public')->path($path);
    $filename = basename($path);

    if (!file_exists($file)) {
        return response()->json(['error' => 'File not found on disk'], 404);
    }

    // Get file info
    $fileSize = filesize($file);
    $mimeType = mime_content_type($file);
    
    // Create a streaming response for better cross-origin handling
    $stream = fopen($file, 'rb');
    
    return response()->stream(
        function() use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        },
        200,
        [
            'Content-Type' => $mimeType,
            'Content-Length' => $fileSize,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Access-Control-Allow-Origin' => config('cors.allowed_origins')[0],
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Expose-Headers' => 'Content-Disposition, Content-Type, Content-Length'
        ]
    );
}
```

### 4. Added Route for Cross-Origin Downloads

Added a dedicated route for cross-origin downloads:

```php
// routes/api.php
Route::middleware('cors')->post('/download-cors/', 'downloadCors');
```

### 5. Frontend Implementation

Created a JavaScript solution for the Vue.js frontend to handle file downloads properly:

```javascript
async function downloadFile(fileName, apiUrl = 'http://localhost:8000/api') {
  try {
    // Create a URL for the download endpoint
    const url = `${apiUrl}/documents/download-signed`;
    
    // Get the CSRF token from the cookie if available
    const csrfToken = getCsrfToken();
    
    // Prepare headers with CSRF token if available
    const headers = {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    };
    
    if (csrfToken) {
      headers['X-CSRF-TOKEN'] = csrfToken;
    }
    
    // Make the request using fetch with proper credentials
    const response = await fetch(url, {
      method: 'POST',
      headers: headers,
      credentials: 'include', // Important for CSRF cookies
      body: JSON.stringify({ file_name: fileName })
    });
    
    if (!response.ok) {
      throw new Error(`Download failed: ${response.status} ${response.statusText}`);
    }
    
    // Get the filename from Content-Disposition header or use the basename
    let downloadFilename = fileName.split('/').pop();
    const contentDisposition = response.headers.get('Content-Disposition');
    if (contentDisposition) {
      const filenameMatch = contentDisposition.match(/filename="(.+)"/);
      if (filenameMatch && filenameMatch[1]) {
        downloadFilename = filenameMatch[1];
      }
    }
    
    // Convert the response to a blob
    const blob = await response.blob();
    
    // Create a download link and trigger the download
    const downloadUrl = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = downloadUrl;
    a.download = downloadFilename;
    document.body.appendChild(a);
    a.click();
    
    // Clean up
    window.URL.revokeObjectURL(downloadUrl);
    document.body.removeChild(a);
    
    return true;
  } catch (error) {
    console.error('Error downloading file:', error);
    throw error;
  }
}
```

## Why This Solution Works

1. **Proper CORS Headers**: By exposing the necessary headers (Content-Disposition, Content-Type, Content-Length), the browser can properly process the download response.

2. **Streaming Response**: Using `response()->stream()` provides better control over the file download process, ensuring the file content is transmitted correctly.

3. **Frontend Blob Handling**: The frontend solution properly handles the response as a blob and creates a download link, which is the recommended approach for file downloads in modern browsers.

4. **CSRF Token Handling**: The frontend solution includes proper CSRF token handling to work with Laravel's security features.

## Implementation Instructions

1. Use the updated backend code as provided in the DocumentController.php file.

2. Integrate the frontend-download-solution.js into your Vue.js application.

3. When triggering downloads from the frontend, use the provided downloadFile function:

```javascript
// Example usage in a Vue component
import { downloadFile } from './frontend-download-solution';

// In your component method
async function handleDownload() {
  try {
    await downloadFile('signed_documents/your-file.pdf');
    // Success handling
  } catch (error) {
    // Error handling
    console.error(error);
  }
}
```

This comprehensive solution addresses all aspects of cross-origin file downloads and should resolve the issue of corrupted downloads.
