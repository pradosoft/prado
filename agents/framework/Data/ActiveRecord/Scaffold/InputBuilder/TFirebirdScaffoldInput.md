# Data/ActiveRecord/Scaffold/InputBuilder/TFirebirdScaffoldInput

### Directories
[framework](../../../../INDEX.md) / [Data](../../../INDEX.md) / [ActiveRecord](../../INDEX.md) / [Scaffold](../INDEX.md) / [InputBuilder](./INDEX.md) / **`TFirebirdScaffoldInput`**

## Class Info
**Location:** `framework/Data/ActiveRecord/Scaffold/InputBuilder/TFirebirdScaffoldInput.php`
**Namespace:** `Prado\Data\ActiveRecord\Scaffold\InputBuilder`

## Overview
`Prado\Data\ActiveRecord\Scaffold\InputBuilder\TFirebirdScaffoldInput`

Firebird-specific scaffold input builder.

Inherits from [`TScaffoldInputCommon`](./TScaffoldInputCommon.md).

## Description

`TFirebirdScaffoldInput` maps Firebird column types to appropriate Prado scaffold input controls.

## Firebird Type Mappings

| Firebird Type | Control Created |
|---------------|-----------------|
| `DATE` | `TDatePicker` |
| `TIME`, `TIME WITH TIME ZONE` | Hour/minute/second dropdowns |
| `TIMESTAMP`, `TIMESTAMP WITH TIME ZONE` | `TDatePicker` + time dropdowns |
| `SMALLINT`, `INTEGER`, `BIGINT` | `TTextBox` with integer validation |
| `FLOAT`, `DOUBLE PRECISION`, `DECIMAL`, `NUMERIC`, `DECFLOAT(16)`, `DECFLOAT(34)` | `TTextBox` with float validation |
| `BOOLEAN` | `TCheckBox` |
| `CHAR`, `VARCHAR` | Single-line `TTextBox` |
| `TEXT`, `BLOB` | Multiline `TTextBox` |

## Key Methods

### `createControl($container, $column, $record)`

Creates the appropriate control based on Firebird column type (case-insensitive comparison).

### `getControlValue($container, $column, $record)`

Gets the control value and converts it to the appropriate type.

## See Also

- [TScaffoldInputCommon](./TScaffoldInputCommon.md)
- [TScaffoldInputBase](./TScaffoldInputBase.md)

## Category

ActiveRecord Scaffold InputBuilder
