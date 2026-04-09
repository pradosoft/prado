# TResultProperty

### Directories

[./](../../INDEX.md) > [Data](../../INDEX.md) > [SqlMap](../INDEX.md) > [Configuration](./INDEX.md) > [TResultProperty](./TResultProperty.md)

`Prado\Data\SqlMap\Configuration\TResultProperty`

Maps a result set column to an object property.

## Description

`TResultProperty` defines how a single column in a result set is mapped to a property in the result object.

## Key Properties

| Property | Type | Description |
|----------|------|-------------|
| `Property` | `string` | Name of the property in the result object |
| `Column` | `string` | Column name or index in the result set |
| `TypeHandler` | `string` | Optional type handler class name |
| `NullValue` | `mixed` | Value to use when column is NULL |
| `NumericNullValue` | `int` | Numeric value to treat as NULL |
| `Select` | `string` | Nested select statement ID for lazy loading |
| `ResultMap` | `string` | Nested result map for complex properties |
| `LazyLoad` | `bool` | Enable lazy loading |

## See Also

- [TResultMap](./TResultMap.md)

## Category

SqlMap Configuration
