# TMysqlScaffoldInput

### Directories

[./](../../INDEX.md) > [Data](../../INDEX.md) > [ActiveRecord](../INDEX.md) > [Scaffold](../INDEX.md) > [InputBuilder](./INDEX.md) > [TMysqlScaffoldInput](./TMysqlScaffoldInput.md)

`Prado\Data\ActiveRecord\Scaffold\InputBuilder\TMysqlScaffoldInput`

MySQL-specific scaffold input builder.

Inherits from [`TScaffoldInputCommon`](./TScaffoldInputCommon.md).

## Description

`TMysqlScaffoldInput` maps MySQL column types to appropriate Prado scaffold input controls.

## MySQL Type Mappings

| MySQL Type | Control Created |
|------------|-----------------|
| `date` | `TDatePicker` |
| `blob`, `tinyblob`, `mediumblob`, `longblob`, `text`, `tinytext`, `mediumtext`, `longtext` | Multiline `TTextBox` |
| `year` | `TDropDownList` (years) |
| `int`, `integer`, `tinyint`, `smallint`, `mediumint`, `bigint` | `TTextBox` with integer validation (or `TCheckBox` if size=1) |
| `decimal`, `double`, `float` | `TTextBox` with float validation |
| `time` | Hour/minute/second dropdowns |
| `datetime`, `timestamp` | `TDatePicker` + time dropdowns |
| `set` | `TCheckBoxList` |
| `enum` | `TRadioButtonList` |

## Special Handling

### TINYINT(1) as Boolean

A `tinyint` column with size of 1 is rendered as a `TCheckBox` instead of a text box.

## Key Methods

### `createControl($container, $column, $record)`

Creates the appropriate control based on MySQL column type.

### `getControlValue($container, $column, $record)`

Gets the control value and converts it to the appropriate type.

### `createIntegerControl($container, $column, $record)`

Creates a boolean checkbox for `tinyint(1)`, otherwise a standard integer control.

## See Also

- [TScaffoldInputCommon](./TScaffoldInputCommon.md)
- [TScaffoldInputBase](./TScaffoldInputBase.md)

## Category

ActiveRecord Scaffold InputBuilder
