# Exceptions/TExitException

### Directories
[framework](../INDEX.md) / [Exceptions](./INDEX.md) / **`TExitException`**

## Class Info
**Location:** `framework/Exceptions/TExitException.php`
**Namespace:** `Prado\Exceptions`

## Overview
TExitException is thrown to gracefully terminate the application with a specified exit code. Unlike PHP's `exit`, this allows proper exception handling and cleanup.

## Hierarchy

```
TExitException
└── [TSystemException](./TSystemException.md)
    └── [TException](./TException.md)
        └── Exception
```

## Key Features

- Allows graceful application termination with a process exit code
- Exit code stored separately from PHP's exception code
- Not meant to be caught by anything other than `TApplication::run()`

## Constructor

```php
public function __construct(int $exitCode = 0, ?string $message = null, ...$args)
```

`$exitCode` is stored via `setExitCodeDirect()` before `parent::__construct()` is called. `$message` and `$args` follow normal `TSystemException` / `TException` conventions (message key + substitution parameters).

## Usage

```php
throw new TExitException(0);              // clean exit
throw new TExitException(1, 'my_error_key', $detail);  // exit with error
```

## Methods

| Method | Description |
|--------|-------------|
| `getExitCode(): int` | Returns the process exit code |
| `getExitCodeDirect(): int` | Protected: returns raw `$_exitCode` field |
| `setExitCodeDirect(int $value): void` | Protected: sets raw `$_exitCode` field |

## Warning

Catching this exception in application code may interfere with graceful termination. It is intentionally not a subclass of `THttpException` and is not handled by `TErrorHandler`.

## See Also

- [TErrorHandler](./TErrorHandler.md) - Does NOT handle TExitException
- [THttpException](./THttpException.md) - For HTTP-status errors
