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

- Allows graceful application termination
- Specify exit code via `getExitCode()`
- Not meant to be caught (except by TApplication)

## Usage

```php
throw new TExitException(0, 'Shutdown complete');

// Or with message
throw new TExitException(1, 'Fatal error occurred');
```

## Methods

### getExitCode

```php
public function getExitCode(): int
```

Returns the exit code that the application should return.

## Warning

Catching this exception may interfere with graceful application termination.

## See Also

- `TApplication` - Catches and handles this exception
