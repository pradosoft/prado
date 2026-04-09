# TPropertyAccess

### Directories

[./](../../INDEX.md) > [Data](../../INDEX.md) > [SqlMap](../INDEX.md) > [DataMapper](./INDEX.md) > [TPropertyAccess](./TPropertyAccess.md)

`Prado\Data\SqlMap\DataMapper\TPropertyAccess`

Static utility for reading/writing object and array properties.

## Description

`TPropertyAccess` provides uniform property access using dot notation. It handles `getXxx()`/`setXxx()` accessors, public fields, and array keys.

## Usage

```php
// Get property
$value = TPropertyAccess::get($obj, 'propertyName');
$value = TPropertyAccess::get($obj, 'property.subproperty');

// Set property
TPropertyAccess::set($obj, 'propertyName', $value);
TPropertyAccess::set($obj, 'property.subproperty', $value);

// Array access
$value = TPropertyAccess::get($array, 'key');
TPropertyAccess::set($array, 'key', $value);
```

## Key Methods

### `get($object, $path)`

Gets a property value using dot notation path.

### `set($object, $path, $value)`

Sets a property value using dot notation path.

## Exceptions

Throws [`TInvalidPropertyException`](./TInvalidPropertyException.md) if the property path is invalid.

## See Also

- `TInvalidPropertyException`

## Category

SqlMap DataMapper
