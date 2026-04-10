# Data/ActiveRecord/Scaffold/InputBuilder/TMssqlScaffoldInput

### Directories
[framework](../../../../INDEX.md) / [Data](../../../INDEX.md) / [ActiveRecord](../../INDEX.md) / [Scaffold](../INDEX.md) / [InputBuilder](./INDEX.md) / **`TMssqlScaffoldInput`**

## Class Info
**Location:** `framework/Data/ActiveRecord/Scaffold/InputBuilder/TMssqlScaffoldInput.php`
**Namespace:** `Prado\Data\ActiveRecord\Scaffold\InputBuilder`

## Overview
`Prado\Data\ActiveRecord\Scaffold\InputBuilder\TMssqlScaffoldInput`

MSSQL-specific scaffold input builder.

Inherits from [`TScaffoldInputCommon`](./TScaffoldInputCommon.md).

## Description

`TMssqlScaffoldInput` maps MSSQL column types to appropriate Prado scaffold input controls.

## MSSQL Type Mappings

| MSSQL Type | Control Created |
|------------|-----------------|
| `bit` | `TCheckBox` |
| `text` | Multiline `TTextBox` |
| `smallint`, `int`, `bigint`, `tinyint` | `TTextBox` with integer validation |
| `decimal`, `float`, `money`, `numeric`, `real`, `smallmoney` | `TTextBox` with float validation |
| `datetime`, `smalldatetime` | `TDatePicker` + time dropdowns |
| Other | Default `TTextBox` (disabled if excluded) |

## Special Handling

### Excluded Columns

Columns marked as `IsExcluded` are rendered as disabled controls.

### Empty String to NULL

If an empty string is submitted for a nullable column, `null` is stored instead.

## Key Methods

### `createControl($container, $column, $record)`

Creates the appropriate control based on MSSQL column type.

### `getControlValue($container, $column, $record)`

Gets the control value, converting empty strings to `null` for nullable columns.

## See Also

- [TScaffoldInputCommon](./TScaffoldInputCommon.md)
- [TScaffoldInputBase](./TScaffoldInputBase.md)

## Category

ActiveRecord Scaffold InputBuilder
