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

- HTTP status code stored separately from PHP's exception code
- Automatic message translation via `TException` message files
- Placeholder replacement in messages (`{0}`, `{1}`, …)
- Recognized by `TErrorHandler` to render user-facing error pages

## Constructor

```php
public function __construct(int $statusCode, string $errorMessage, ...$args)
```

- `$statusCode` — HTTP status code; cast to `int` and stored via `setStatusCodeDirect()`.
- `$errorMessage` — Message key (looked up in messages files) or literal string.
- `$args` — Substitution values for `{0}`, `{1}`, … placeholders; trailing `Throwable` is chained.

```php
throw new THttpException(404, 'page_not_found', $pagePath);
throw new THttpException(403, 'access_denied');
throw new THttpException(500, 'internal_server_error');
```

## Methods

| Method | Description |
|--------|-------------|
| `getStatusCode(): int` | Returns the HTTP status code |
| `getStatusCodeDirect(): int` | Protected: returns raw `$_statusCode` field |
| `setStatusCodeDirect(int $value): void` | Protected: sets raw `$_statusCode` field |

## How TErrorHandler Uses It

`TErrorHandler::handleError()` detects `THttpException` and calls `handleExternalError($statusCode, $exception)`, which selects the matching error template and sets the HTTP response status code accordingly.

Non-`THttpException` exceptions in non-Debug mode result in a generic HTTP 500 response.

## See Also

- [TErrorHandler](./TErrorHandler.md) - Renders user-facing error pages
- [TExitException](./TExitException.md) - Graceful application exit (not an HTTP error)
