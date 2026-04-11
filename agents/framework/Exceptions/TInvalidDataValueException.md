# Exceptions/TInvalidDataValueException

### Directories
[framework](../INDEX.md) / [Exceptions](./INDEX.md) / **`TInvalidDataValueException`**

## Class Info
**Location:** `framework/Exceptions/TInvalidDataValueException.php`
**Namespace:** `Prado\Exceptions`

## Overview
Represents an exception caused by an invalid value for a valid data type. Extends `TSystemException`.

## Hierarchy

```
TInvalidDataValueException
└── [TSystemException](./TSystemException.md)
    └── [TException](./TException.md)
        └── Exception
```

## Usage

```php
throw new TInvalidDataValueException('invalid_value', $value, 'positive integer');
```

## Common Error Codes

- Index out of range
- Negative value when positive expected
- Empty string when non-empty required
- Invalid enum value

## See Also

- `[TInvalidDataTypeException](./TInvalidDataTypeException.md)` - For wrong data types
- `[TInvalidOperationException](./TInvalidOperationException.md)` - For invalid operations
