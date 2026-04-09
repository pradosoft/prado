# TInvalidDataTypeException

### Directories
[./](../INDEX.md) > [Exceptions](./INDEX.md) > [TInvalidDataTypeException](./TInvalidDataTypeException.md)

**Location:** `framework/Exceptions/TInvalidDataTypeException.php`
**Namespace:** `Prado\Exceptions`

## Overview

Represents an exception caused by passing data of an invalid type. Extends `TSystemException`.

## Hierarchy

```
TInvalidDataTypeException
└── [TSystemException](./TSystemException.md)
    └── [TException](./TException.md)
        └── Exception
```

## Usage

```php
throw new TInvalidDataTypeException('invalid_datatype', gettype($value), expected_type);
```

## Common Error Codes

- Passing array when object expected
- Passing string when integer expected
- Passing null to non-nullable parameter

## See Also

- `[TInvalidDataValueException](./TInvalidDataValueException.md)` - For valid type but invalid value
- `[TInvalidOperationException](./TInvalidOperationException.md)` - For invalid operations
