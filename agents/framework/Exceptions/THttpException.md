# Exceptions/THttpException

### Directories
[framework](../INDEX.md) / [Exceptions](./INDEX.md) / **`THttpException`**

## Class Info
**Location:** `framework/Exceptions/THttpException.php`
**Namespace:** `Prado\Exceptions`

## Overview
THttpException represents an exception caused by end-user operations. The status code indicates the HTTP response status (404, 500, etc.). Used by `TErrorHandler` to render appropriate error pages.

## Hierarchy

```
THttpException
└── [TSystemException](./TSystemException.md)
    └── [TException](./TException.md)
        └── Exception
```

## Key Features

- HTTP status code embedded in exception
- Automatic message translation
- Placeholder replacement in messages

## Usage

```php
throw new THttpException(404, 'page_not_found', 'Page', '/home');

// Common codes:
// 400 - Bad Request
// 401 - Unauthorized
// 403 - Forbidden
// 404 - Not Found
// 500 - Internal Server Error
```

## Constructor

```php
public function __construct(int $statusCode, string $errorMessage, ...$args)
```

- `$statusCode` - HTTP status code (404, 500, etc.)
- `$errorMessage` - Error message or error code from messages file
- `$args` - Parameters for message placeholders

## Methods

### getStatusCode

```php
public function getStatusCode(): int
```

Returns the HTTP status code.

## See Also

- `[TErrorHandler](./TErrorHandler.md)` - Handles this exception
