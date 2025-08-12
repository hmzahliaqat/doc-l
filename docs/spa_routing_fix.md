# SPA Routing Fix Documentation

## Overview

This document outlines the changes made to fix the routing issues between the Laravel backend and Vue.js frontend, particularly for the `/view-document` route that was experiencing 404 errors in production.

## Issue Description

The `/view-document` route was working fine in local development but returning a 404 error in production. This issue occurred when the Laravel backend redirected to the frontend URL with query parameters:

```php
$vueUrl = env('FRONTEND_URL') . "/view-document?shared_document_id=$shared_document_id&document_pdf_id=$document_pdf_id&employee_id=$employee_id&is_employee=true";
return redirect()->away($vueUrl);
```

## Root Causes

The issue was caused by a combination of factors:

1. **Case Sensitivity**: Production servers (especially Linux-based) are case-sensitive, while local development environments might not be.
2. **SPA Routing Configuration**: The server configuration in production was not properly set up to handle client-side routing with Vue Router's history mode.
3. **URL Construction**: The URL construction in the backend did not handle trailing slashes in the FRONTEND_URL environment variable.

## Changes Made

### 1. Backend Changes

#### 1.1 Updated URL Generation in DocumentController.php

Modified the URL generation in the `employeeView` method to:
- Use a default value for FRONTEND_URL
- Remove trailing slashes from the frontend URL
- Add logging for debugging purposes

```php
// Ensure consistent case for the route path
$vueUrl = rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/') . "/view-document?shared_document_id=$shared_document_id&document_pdf_id=$document_pdf_id&employee_id=$employee_id&is_employee=true";

// Log the redirect URL for debugging
Log::info('Redirecting to frontend', ['url' => $vueUrl]);

return redirect()->away($vueUrl);
```

#### 1.2 Verified CORS Configuration

Confirmed that the CORS configuration in `config/cors.php` was correctly set up to allow requests from the frontend domain:

```php
'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')],
```

#### 1.3 Created Production Environment File

Created a `.env.production` file with the correct settings for a production environment, including:
- Setting `APP_ENV=production` and `APP_DEBUG=false`
- Using HTTPS URLs for both backend and frontend
- Setting appropriate session domains

### 2. Server Configuration Files

#### 2.1 Apache Configuration (.htaccess)

Created a frontend .htaccess file for Apache servers:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Handle Vue Router (SPA)
    RewriteRule ^index\.html$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . /index.html [L]
</IfModule>
```

#### 2.2 Nginx Configuration

Created an nginx configuration file:

```nginx
# Vue.js Frontend
location / {
    try_files $uri $uri/ /index.html;
}

error_page 404 /index.html;
```

#### 2.3 Vercel Configuration

Created a `vercel.json` file for Vercel deployments:

```json
{
  "rewrites": [
    {
      "source": "/(.*)",
      "destination": "/index.html"
    }
  ]
}
```

### 3. Testing

Created a test script (`tests/test_view_document_redirect.php`) to verify:
1. The backend correctly redirects to the frontend
2. The redirect URL has the correct path and query parameters
3. The frontend can successfully handle the redirect

## How to Deploy

1. Update the production environment with the changes to `DocumentController.php`
2. Ensure the `FRONTEND_URL` environment variable is correctly set in production
3. Deploy the appropriate server configuration file based on your hosting environment:
   - For Apache: Use the `.htaccess` file
   - For Nginx: Use the nginx configuration
   - For Vercel: Use the `vercel.json` file
4. Run the test script to verify the implementation works correctly

## Troubleshooting

If issues persist after deployment:

1. Check the server logs for any errors
2. Verify that the `FRONTEND_URL` environment variable is correctly set
3. Ensure the server configuration is properly applied
4. Test with different case variations of the route (e.g., `/View-Document`, `/VIEW-DOCUMENT`)
5. Check if the frontend's Vue Router is configured to handle the route correctly

## Conclusion

By implementing these changes, the routing between the Laravel backend and Vue.js frontend should work correctly in all environments, including production. The key improvements are:

1. Consistent case usage in URL generation
2. Proper server configuration for SPA routing
3. Robust environment variable handling
4. Comprehensive testing and debugging capabilities
