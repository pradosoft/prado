# Exceptions/TDbException

### Directories
[framework](../INDEX.md) / [Exceptions](./INDEX.md) / **`TDbException`**

## Class Info
**Location:** `framework/Exceptions/TDbException.php`
**Namespace:** `Prado\Exceptions`

## Overview
Represents an exception related to database operations. Extends `TSystemException`.

## Hierarchy

```
TDbException
└── [TSystemException](./TSystemException.md)
    └── [TException](./TException.md)
        └── Exception
```

## Subclasses

- `[TDbConnectionException](./TDbConnectionException.md)` - Connection failures

## Usage

```php
throw new TDbException('db_query_error', $sqlState, $errorCode);
```

## See Also

- `[TDbConnectionException](./TDbConnectionException.md)` - For connection issues
- `TDbCommand` - Database command class
