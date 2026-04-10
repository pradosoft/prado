# Exceptions/TUserException

### Directories
[framework](../INDEX.md) / [Exceptions](./INDEX.md) / **`TUserException`**

## Class Info
**Location:** `framework/Exceptions/TUserException.php`
**Namespace:** `Prado\Exceptions`

## Overview
Base class for exceptions designed for display to end users. These are typically caused by user mistakes (invalid input, wrong permissions, etc.). Extends `TApplicationException`.

## Hierarchy

```
TUserException
└── [TApplicationException](./TApplicationException.md)
    └── [TException](./TException.md)
        └── Exception
```

## Usage

```php
throw new TUserException('invalid_username');
```

## Design Principle

- Messages should be user-friendly and in the user's language
- Generally caught and displayed by `TErrorHandler` with external template
- Not for system/programming errors

## See Also

- `[TApplicationException](./TApplicationException.md)` - Application-level exceptions
- `[TErrorHandler](./TErrorHandler.md)` - Error display
