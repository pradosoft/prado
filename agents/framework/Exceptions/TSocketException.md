# Exceptions/TSocketException

### Directories
[framework](../INDEX.md) / [Exceptions](./INDEX.md) / **`TSocketException`**

## Class Info
**Location:** `framework/Exceptions/TSocketException.php`
**Namespace:** `Prado\Exceptions`

## Overview
Handles all socket-related exceptions. Extends `TNetworkException`.

## Hierarchy

```
TSocketException
└── [TNetworkException](./TNetworkException.md)
    └── [TException](./TException.md)
        └── Exception
```

## Key Features

- Manages socket error codes
- Auto-translates error codes to messages

## Constructor

```php
public function __construct(int $errorCode, string $errorMessage = null)
```

If `$errorMessage` is null, it uses PHP's `socket_strerror()`.

## Usage

```php
throw new TSocketException(socket_last_error(), 'Connection refused');
```

## See Also

- `[TNetworkException](./TNetworkException.md)` - Base network exception
