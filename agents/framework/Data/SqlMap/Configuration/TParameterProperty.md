# Data/SqlMap/Configuration/TParameterProperty

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [SqlMap](../INDEX.md) / [Configuration](./INDEX.md) / **`TParameterProperty`**

## Class Info
**Location:** `framework/Data/SqlMap/Configuration/TParameterProperty.php`
**Namespace:** `Prado\Data\SqlMap\Configuration`

## Overview
`Prado\Data\SqlMap\Configuration\TParameterProperty`

Maps an object property to a SQL parameter.

## Description

`TParameterProperty` defines how a property in a PHP object is mapped to a parameter in a SQL statement.

## Key Properties

| Property | Type | Description |
|----------|------|-------------|
| `Property` | `string` | Name of the property in the PHP object |
| `Column` | `string` | Column name in the SQL statement |
| `Type` | `string` | JDBC type (e.g., `VARCHAR`, `INTEGER`) |
| `TypeHandler` | `string` | Optional type handler class name |
| `NullValue` | `mixed` | Value to treat as NULL when saving |
| `NumericNullValue` | `int` | Numeric value to treat as NULL |

## See Also

- [TParameterMap](./TParameterMap.md)

## Category

SqlMap Configuration
