# Data/ActiveRecord/Scaffold/InputBuilder/TPgsqlScaffoldInput

### Directories
[framework](../../../../INDEX.md) / [Data](../../../INDEX.md) / [ActiveRecord](../../INDEX.md) / [Scaffold](../INDEX.md) / [InputBuilder](./INDEX.md) / **`TPgsqlScaffoldInput`**

## Class Info
**Location:** `framework/Data/ActiveRecord/Scaffold/InputBuilder/TPgsqlScaffoldInput.php`
**Namespace:** `Prado\Data\ActiveRecord\Scaffold\InputBuilder`

## Overview
`Prado\Data\ActiveRecord\Scaffold\InputBuilder\TPgsqlScaffoldInput`

PostgreSQL-specific scaffold input builder.

Inherits from [`TScaffoldInputCommon`](./TScaffoldInputCommon.md).

## Description

`TPgsqlScaffoldInput` maps PostgreSQL column types to appropriate Prado scaffold input controls.

## PostgreSQL Type Mappings

| PostgreSQL Type | Control Created |
|-----------------|-----------------|
| `boolean` | `TCheckBox` |
| `date` | `TDatePicker` |
| `text` | Multiline `TTextBox` |
| `smallint`, `integer`, `bigint` | `TTextBox` with integer validation |
| `decimal`, `numeric`, `real`, `double precision` | `TTextBox` with float validation |
| `time without time zone` | Hour/minute/second dropdowns |
| `timestamp without time zone` | `TDatePicker` + time dropdowns |
| Other | Default `TTextBox` |

## Key Methods

### `createControl($container, $column, $record)`

Creates the appropriate control based on PostgreSQL column type.

### `getControlValue($container, $column, $record)`

Gets the control value and converts it to the appropriate type.

## See Also

- [TScaffoldInputCommon](./TScaffoldInputCommon.md)
- [TScaffoldInputBase](./TScaffoldInputBase.md)

## Category

ActiveRecord Scaffold InputBuilder
