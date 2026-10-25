# Data/ActiveRecord/Scaffold/InputBuilder/TOracleScaffoldInput

### Directories
[framework](../../../../INDEX.md) / [Data](../../../INDEX.md) / [ActiveRecord](../../INDEX.md) / [Scaffold](../INDEX.md) / [InputBuilder](./INDEX.md) / **`TOracleScaffoldInput`**

## Class Info
**Location:** `framework/Data/ActiveRecord/Scaffold/InputBuilder/TOracleScaffoldInput.php`
**Namespace:** `Prado\Data\ActiveRecord\Scaffold\InputBuilder`
**Since:** 4.4.0

## Overview
`Prado\Data\ActiveRecord\Scaffold\InputBuilder\TOracleScaffoldInput`

Oracle-specific scaffold input builder, selected by `TScaffoldInputBase::createInputBuilder()` for the `oci` driver.

Inherits from [`TScaffoldInputCommon`](./TScaffoldInputCommon.md).

## Description

`TOracleScaffoldInput` maps Oracle column types to Prado scaffold input controls. `DbType` comes from `TOracleMetaData`: the `ALL_TAB_COLUMNS` data type without its precision, so `NUMBER(10,0)` is `NUMBER` and `TIMESTAMP(6) WITH TIME ZONE` is `TIMESTAMP WITH TIME ZONE`. Types are compared in uppercase.

## Oracle Type Mappings

| Oracle Type | Control Created |
|-------------|-----------------|
| `BOOLEAN` | `TCheckBox` |
| `DATE`, `TIMESTAMP`, `TIMESTAMP WITH TIME ZONE`, `TIMESTAMP WITH LOCAL TIME ZONE` | `TDatePicker` + time dropdowns (Oracle `DATE` holds a time) |
| `INTEGER`, `INT`, `SMALLINT`; `NUMBER`, `DECIMAL`, `NUMERIC` with `NumericScale` 0 | `TTextBox` with integer validation |
| `NUMBER`, `DECIMAL`, `NUMERIC` with another or no scale; `FLOAT`, `BINARY_FLOAT`, `BINARY_DOUBLE`, `REAL`, `DOUBLE PRECISION` | `TTextBox` with float validation |
| `CLOB`, `NCLOB`, `LONG`, `BLOB` | Multiline `TTextBox` |
| other (`VARCHAR2`, `NVARCHAR2`, `CHAR`, `NCHAR`, ...) | `TTextBox` with `MaxLength` from `ColumnSize` |

An unconstrained `NUMBER` has no `NumericScale` and takes the float control.

## Key Methods

### `createControl($container, $column, $record)`

Creates the control for the column type.

### `getControlValue($container, $column, $record)`

Reads the value back: `BOOLEAN` → checked state, the date types → `Y-m-d H:i:s` from `getDateTimeValue()`, others → the text box text.

## See Also

- [TScaffoldInputCommon](./TScaffoldInputCommon.md)
- [TScaffoldInputBase](./TScaffoldInputBase.md)
- [TOracleMetaData](../../../Common/Oracle/TOracleMetaData.md)

## Category

ActiveRecord Scaffold InputBuilder
