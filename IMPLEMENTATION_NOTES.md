    # User Role API Implementation

## Overview
This implementation adds a new API endpoint that returns the role of the currently authenticated user.

## Changes Made

1. Created a new `UserController` with a `getRole` method:
   - Located at: `app/Http/Controllers/UserController.php`
   - The method retrieves the authenticated user's role using Spatie's Permission package

2. Added a new API route:
   - Added to: `routes/api.php`
   - Route: `GET /api/user/role`
   - Protected by the `auth:sanctum` middleware to ensure only authenticated users can access it

## How to Use

To get the current user's role, make an authenticated GET request to:
```
/api/user/role
```

### Example Response
```json
{
  "role": "super-admin"
}
```

## Testing
A test script has been created at `test_user_role.php` to verify the functionality, but it requires a valid CSRF token and authenticated session to work properly.

## Notes
- The implementation leverages the existing Spatie Permission package that's already being used in the project
- The endpoint returns the first role assigned to the user (users can have multiple roles in Spatie's Permission package)
- The endpoint is protected by authentication to ensure only logged-in users can access it
