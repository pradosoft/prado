# Data/SqlMap/DataMapper/TSqlMapTypeHandler

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [SqlMap](../INDEX.md) / [DataMapper](./INDEX.md) / **`TSqlMapTypeHandler`**

## Class Info
**Location:** `framework/Data/SqlMap/DataMapper/TSqlMapTypeHandler.php`
**Namespace:** `Prado\Data\SqlMap\DataMapper`

## Overview
`Prado\Data\SqlMap\DataMapper\TSqlMapTypeHandler`

Abstract base class for custom type handlers.

## Description

`TSqlMapTypeHandler` is the abstract base class for custom type handlers. Implement `getParameter($object)` for PHP → SQL conversion and `getResult($type, $value)` for SQL → PHP conversion.

## Implementing a Custom Type Handler

```php
class MyDateHandler extends TSqlMapTypeHandler
{
    public function getParameter($object)
    {
        return $object->format('Y-m-d');
    }

    public function getResult($type, $value)
    {
        return new DateTime($value);
    }
}
```

## Key Methods

### `getParameter($object)` (abstract)

Converts a PHP object to a database parameter value.

### `getResult($type, $value)` (abstract)

Converts a database result value to a PHP object.

## See Also

- [TSqlMapTypeHandlerRegistry](./TSqlMapTypeHandlerRegistry.md)

## Category

SqlMap DataMapper
