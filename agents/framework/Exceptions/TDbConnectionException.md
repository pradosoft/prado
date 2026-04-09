# TDbConnectionException

### Directories
[./](../INDEX.md) > [Exceptions](./INDEX.md) > [TDbConnectionException](./TDbConnectionException.md)

**Location:** `framework/Exceptions/TDbConnectionException.php`
**Namespace:** `Prado\Exceptions`

## Overview

Represents an exception caused by database connection failure. Extends `[TDbException](./TDbException.md)`.

## Hierarchy

```
TDbConnectionException
└── TDbException
    └── TSystemException
        └── TException
            └── Exception
```

## Usage

```php
throw new TDbConnectionException('db_connection_failed');
```

## Common Causes

- Invalid connection string
- Database server not running
- Wrong credentials
- Network connectivity issues

## See Also

- `[TDbException](./TDbException.md)` - General database exceptions
- `TDbConnection` - Database connection class
