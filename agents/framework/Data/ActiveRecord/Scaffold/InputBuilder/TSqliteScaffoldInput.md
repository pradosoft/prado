# Data/ActiveRecord/Scaffold/InputBuilder/TSqliteScaffoldInput

### Directories
[framework](../../../../INDEX.md) / [Data](../../../INDEX.md) / [ActiveRecord](../../INDEX.md) / [Scaffold](../INDEX.md) / [InputBuilder](./INDEX.md) / **`TSqliteScaffoldInput`**

## Class Info
**Location:** `framework/Data/ActiveRecord/Scaffold/InputBuilder/TSqliteScaffoldInput.php`
**Namespace:** `Prado\Data\ActiveRecord\Scaffold\InputBuilder`

## Overview
`Prado\Data\ActiveRecord\Scaffold\InputBuilder\TSqliteScaffoldInput`

SQLite-specific scaffold input builder.

Inherits from [`TScaffoldInputCommon`](./TScaffoldInputCommon.md).

## Description

`TSqliteScaffoldInput` maps SQLite column types to appropriate Prado scaffold input controls.

## SQLite Type Mappings

| SQLite Type | Control Created |
|-------------|-----------------|
| `boolean` | `TCheckBox` |
| `date` | `TDatePicker` |
| `blob`, `tinyblob`, `mediumblob`, `longblob`, `text`, `tinytext`, `mediumtext`, `longtext` | Multiline `TTextBox` |
| `year` | `TDropDownList` (years) |
| `int`, `integer`, `tinyint`, `smallint`, `mediumint`, `bigint` | `TTextBox` with integer validation |
| `decimal`, `double`, `float` | `TTextBox` with float validation |
| `time` | Hour/minute/second dropdowns |
| `datetime`, `timestamp` | `TDatePicker` + time dropdowns |

## Special Handling

### Timestamp Columns

SQLite `timestamp` columns handle Unix timestamps specially:
- Dates are displayed using `TDatePicker::setTimestamp()`
- Values are stored/retrieved as Unix timestamps

## Key Methods

### `createControl($container, $column, $record)`

Creates the appropriate control based on SQLite column type.

### `getControlValue($container, $column, $record)`

Gets the control value and converts it to the appropriate type.

### `createDateControl($container, $column, $record)`

Creates date control with timestamp support.

### `createDateTimeControl($container, $column, $record)`

Creates datetime control with timestamp support.

### `getDateTimeValue($container, $column, $record)`

Returns Unix timestamp for timestamp columns.

## See Also

- [TScaffoldInputCommon](./TScaffoldInputCommon.md)
- [TScaffoldInputBase](./TScaffoldInputBase.md)

## Category

ActiveRecord Scaffold InputBuilder
