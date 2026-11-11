# Data/ActiveRecord/Scaffold/InputBuilder/TSqlSrvScaffoldInput

### Directories
[framework](../../../../INDEX.md) / [Data](../../../INDEX.md) / [ActiveRecord](../../INDEX.md) / [Scaffold](../INDEX.md) / [InputBuilder](./INDEX.md) / **`TSqlSrvScaffoldInput`**

## Class Info
**Location:** `framework/Data/ActiveRecord/Scaffold/InputBuilder/TSqlSrvScaffoldInput.php`
**Namespace:** `Prado\Data\ActiveRecord\Scaffold\InputBuilder`

## Overview
`Prado\Data\ActiveRecord\Scaffold\InputBuilder\TSqlSrvScaffoldInput`

SQL Server scaffold input builder for the `sqlsrv` and `dblib` drivers and the `mssql` extension (@since 4.3.3; it replaced `TMssqlScaffoldInput`).

Inherits from [`TScaffoldInputCommon`](./TScaffoldInputCommon.md).

## Description

`TSqlSrvScaffoldInput` maps SQL Server column types to appropriate Prado scaffold input controls.

## SQL Server Type Mappings

| SQL Server Type | Control Created |
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

Creates the appropriate control based on the SQL Server column type.

### `getControlValue($container, $column, $record)`

Gets the control value, converting empty strings to `null` for nullable columns.

## See Also

- [TScaffoldInputCommon](./TScaffoldInputCommon.md)
- [TScaffoldInputBase](./TScaffoldInputBase.md)

## Category

ActiveRecord Scaffold InputBuilder
