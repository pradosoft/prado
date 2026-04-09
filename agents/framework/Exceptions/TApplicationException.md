# TApplicationException

### Directories
[./](../INDEX.md) > [Exceptions](./INDEX.md) > [TApplicationException](./TApplicationException.md)

**Location:** `framework/Exceptions/TApplicationException.php`
**Namespace:** `Prado\Exceptions`

## Overview

Base class for all user application-level exceptions. Extends `[TException](./TException.md)`.

## Hierarchy

```
TApplicationException
└── TException
    └── Exception
```

## Usage

```php
throw new TApplicationException('app_error_code', $param1, $param2);
```

## Related Exceptions

- `[TUserException](./TUserException.md)` - For end-user displayable errors
- `[TLogException](./TLogException.md)` - For logging-related errors

## See Also

- `[TException](./TException.md)` - Base exception class
