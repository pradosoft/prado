# TPhpErrorException

### Directories
[./](../INDEX.md) > [Exceptions](./INDEX.md) > [TPhpErrorException](./TPhpErrorException.md)

**Location:** `framework/Exceptions/TPhpErrorException.php`
**Namespace:** `Prado\Exceptions`

## Overview

Represents an exception caused by a PHP error. This is typically thrown within a PHP error handler. Extends `TSystemException`.

## Hierarchy

```
TPhpErrorException
└── [TSystemException](./TSystemException.md)
    └── [TException](./TException.md)
        └── Exception
```

## Key Features

- Wraps PHP errors as exceptions
- Includes error type, message, file, and line
- Static helper to detect fatal errors

## Constructor

```php
public function __construct(int $errno, string $errstr, string $errfile, int $errline)
```

- `$errno` - PHP error constant (E_ERROR, E_WARNING, etc.)
- `$errstr` - Error message
- `$errfile` - File where error occurred
- `$errline` - Line number

## Static Methods

### isFatalError

```php
public static function isFatalError(array $error): bool
```

Checks if the error is a fatal-type error (E_ERROR, E_PARSE, etc.).

## Usage

```php
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new TPhpErrorException($errno, $errstr, $errfile, $errline);
});
```

## See Also

- `[TErrorHandler](./TErrorHandler.md)` - Error handling module
