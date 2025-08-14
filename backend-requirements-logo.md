# Logo API Implementation Documentation

## Overview

This document outlines the implementation of the logo API endpoint that allows the frontend to fetch the application logo from the backend. This implementation ensures that the frontend always uses the logo provided by the backend, which is especially important for white-labeled deployments.

## Current Implementation

The backend now provides a dedicated API endpoint for fetching the application logo:

```
GET /api/settings/logo
```

**Response:**
```json
{
  "logo_url": "http://localhost:8000/storage/logos/1754815464_logo22.png",
  "alt_text": "Clickesignature"
}
```

If no custom logo has been uploaded, the endpoint returns the default logo path:

```json
{
  "logo_url": "/logo-dark.png",
  "alt_text": "Clickesignature"
}
```

## Implementation Details

### Backend Changes

1. **Added Logo API Endpoint**
   - Created a new method `getLogo()` in the `SuperAdminController` that returns the logo URL and alt text
   - Added a new route `/api/settings/logo` in `routes/api.php` that maps to the `getLogo()` method

2. **Logo Storage**
   - Logos are stored in the `public/storage/logos` directory
   - The logo path is stored in the `app_logo` field of the `super_admin_settings` table
   - The full URL is generated using Laravel's `Storage::url()` helper

3. **Fallback Mechanism**
   - If no logo is set in the database, the endpoint returns the default logo path `/logo-dark.png`
   - The alt text defaults to "Clickesignature" if no app name is set

### API Endpoint Specification

**Endpoint:** `GET /api/settings/logo`

**Authentication:** None (publicly accessible)

**Response Format:**
```json
{
  "logo_url": "string",
  "alt_text": "string"
}
```

**Response Fields:**
- `logo_url`: The URL to the logo image
- `alt_text`: The alternative text for the logo image

**Status Codes:**
- `200 OK`: The request was successful
- `500 Internal Server Error`: An error occurred on the server

## Security Considerations

- The endpoint is publicly accessible without authentication
- Only the logo URL and alt text are exposed, no sensitive information
- The logo files are stored in the public storage directory, accessible via direct URL

## Performance Considerations

- The logo URL is cached in the frontend to reduce API calls
- The logo image should be optimized for web (compressed, appropriate size)
- Consider implementing caching on the backend for high-traffic deployments

## Frontend Integration

The frontend should use the `useLogo` composable to fetch the logo from the API and update the config store. The composable handles:
- Fetching the logo from the API
- Updating the config store with the logo URL
- Error handling with fallback to default logo
- Loading state management

## Testing

The implementation has been tested to ensure:
1. The logo is fetched from the API successfully
2. The response contains the correct logo URL and alt text
3. The fallback mechanism works when no logo is set

## Maintenance

When updating or modifying the logo functionality:
1. Ensure the response format remains consistent
2. Test both the success case and fallback case
3. Verify that the frontend correctly displays the logo
