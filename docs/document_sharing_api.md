# Document Sharing API Documentation

This document describes the API endpoints for sharing documents with employees.

## Authentication

All document sharing endpoints require authentication using a Bearer token. Include the token in the Authorization header of your requests:

```
Authorization: Bearer your-token-here
```

## Rate Limiting

The bulk-share endpoint is rate-limited to 10 requests per minute to prevent abuse.

## Endpoints

### 1. Share a Document

Share a document with one or more employees.

```
POST /api/documents/share
```

#### Request Payload (Single Employee)

```json
{
  "document_id": "document-id-123",
  "employee_id": 123
}
```

#### Request Payload (Multiple Employees)

```json
{
  "document_id": "document-id-123",
  "employee_ids": [123, 124, 125]
}
```

#### Response

```json
{
  "success": true,
  "message": "Document shared successfully",
  "shares": [
    {
      "document_id": "document-id-123",
      "employee_id": 123,
      "shared_at": "2025-08-12T08:45:30Z"
    },
    {
      "document_id": "document-id-123",
      "employee_id": 124,
      "shared_at": "2025-08-12T08:45:30Z"
    },
    {
      "document_id": "document-id-123",
      "employee_id": 125,
      "shared_at": "2025-08-12T08:45:30Z"
    }
  ]
}
```

### 2. Bulk Share Documents

Share multiple documents with one or more employees.

```
POST /api/documents/bulk-share
```

#### Request Payload

```json
{
  "document_ids": ["document-id-123", "document-id-456", "document-id-789"],
  "employee_ids": [123, 124]
}
```

#### Response

```json
{
  "success": true,
  "message": "Documents shared successfully",
  "total_shares": 6,
  "shares": [
    {
      "document_id": "document-id-123",
      "employee_id": 123,
      "shared_at": "2025-08-12T08:45:30Z"
    },
    {
      "document_id": "document-id-123",
      "employee_id": 124,
      "shared_at": "2025-08-12T08:45:30Z"
    },
    {
      "document_id": "document-id-456",
      "employee_id": 123,
      "shared_at": "2025-08-12T08:45:30Z"
    },
    "..."
  ]
}
```

## Error Responses

### Authentication Error

```json
{
  "message": "Unauthenticated."
}
```

### Validation Error

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "document_id": [
      "The document id field is required."
    ],
    "employee_id": [
      "The employee id field is required when employee ids is not present."
    ]
  }
}
```

### Permission Error

```json
{
  "success": false,
  "message": "You don't have permission to share this document."
}
```

### Rate Limit Error

```json
{
  "message": "Too Many Attempts."
}
```

## Security Considerations

1. **Authentication**: All document sharing endpoints require authentication.
2. **Authorization**: Users can only share documents they own.
3. **Rate Limiting**: The bulk-share endpoint is rate-limited to prevent abuse.
4. **Validation**: All input is validated to ensure it meets the required format and constraints.
5. **Logging**: Unauthorized access attempts are logged for security auditing.

## Testing

A test script is provided at `/tests/test_share_endpoint.php` to test the document sharing endpoints. To use it:

1. Update the script with valid credentials and document/employee IDs.
2. Run the script with PHP:

```bash
php tests/test_share_endpoint.php
```

The script tests all three scenarios:
1. Sharing a document with a single employee
2. Sharing a document with multiple employees
3. Bulk sharing multiple documents with multiple employees
