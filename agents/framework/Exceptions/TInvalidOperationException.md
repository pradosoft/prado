# TInvalidOperationException

### Directories
[./](../INDEX.md) > [Exceptions](./INDEX.md) > [TInvalidOperationException](./TInvalidOperationException.md)

**Location:** `framework/Exceptions/TInvalidOperationException.php`
**Namespace:** `Prado\Exceptions`

## Overview

Represents an exception caused by an invalid operation, such as calling a method in an invalid state. Extends `TSystemException`.

## Hierarchy

```
TInvalidOperationException
└── [TSystemException](./TSystemException.md)
    └── [TException](./TException.md)
        └── Exception
```

## Usage

```php
throw new TInvalidOperationException('operation_not_allowed', get_class($this));
```

## Common Error Codes

- Modifying read-only property
- Operation after object disposal
- Accessing uninitialized state

## See Also

- `[TInvalidDataTypeException](./TInvalidDataTypeException.md)` - For type errors
- `[TInvalidDataValueException](./TInvalidDataValueException.md)` - For value errors
