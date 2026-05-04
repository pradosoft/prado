# Exceptions/TLogException

### Directories
[framework](../INDEX.md) / [Exceptions](./INDEX.md) / **`TLogException`**

## Class Info
**Location:** `framework/Exceptions/TLogException.php`
**Namespace:** `Prado\Exceptions`

## Overview
Thrown when there is an exception related to logging operations. Extends `TApplicationException`.

## Hierarchy

```
TLogException
└── [TApplicationException](./TApplicationException.md)
    └── [TException](./TException.md)
        └── Exception
```

## Usage

```php
throw new TLogException('log_write_failed', $logPath);
```

## See Also

- `TLogger` - Logging component
- `TLogRouter` - Log routing
