# Exceptions/TPhpErrorException

### Directories
[framework](../INDEX.md) / [Exceptions](./INDEX.md) / **`TPhpErrorException`**

## Class Info
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

Returns whether an `error_get_last()` array has a type that stops PHP. `Prado::phpFatalErrorHandler()` reports such an error at shutdown as a `TPhpFatalErrorException`.

| `$error['type']` | Result |
|---|---|
| `E_ERROR`, `E_PARSE`, `E_CORE_ERROR`, `E_COMPILE_ERROR` | `true` |
| `E_CORE_WARNING`, `E_COMPILE_WARNING` | `false`; PHP continues after a warning |
| `E_USER_ERROR`, `E_RECOVERABLE_ERROR` | `false`; `Prado::phpErrorHandler()` throws for them before PHP stops |
| any other type, a missing `type`, or `null` | `false` |

## Usage

```php
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new TPhpErrorException($errno, $errstr, $errfile, $errline);
});
```

## See Also

- `[TErrorHandler](./TErrorHandler.md)` - Error handling module
