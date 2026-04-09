# TScaffoldInputCommon

### Directories

[./](../../INDEX.md) > [Data](../../INDEX.md) > [ActiveRecord](../INDEX.md) > [Scaffold](../INDEX.md) > [InputBuilder](./INDEX.md) > [TScaffoldInputCommon](./TScaffoldInputCommon.md)

`Prado\Data\ActiveRecord\Scaffold\InputBuilder\TScaffoldInputCommon`

Common scaffold input builder methods for all database drivers.

Inherits from [`TScaffoldInputBase`](./TScaffoldInputBase.md).

## Description

`TScaffoldInputCommon` provides common methods for creating scaffold input controls that work across all database drivers. It extends `TScaffoldInputBase` with implementations for standard data types.

## Control Creation Methods

### `createBooleanControl($container, $column, $record)`

Creates a `TCheckBox` for boolean columns.

### `createDefaultControl($container, $column, $record)`

Creates a `TTextBox` for standard columns.

### `createMultiLineControl($container, $column, $record)`

Creates a multiline `TTextBox` for text/blob columns.

### `createIntegerControl($container, $column, $record)`

Creates a `TTextBox` with `TDataTypeValidator` for integers.

### `createFloatControl($container, $column, $record)`

Creates a `TTextBox` with validation for decimal numbers.

### `createDateControl($container, $column, $record)`

Creates a `TDatePicker` for date columns.

### `createTimeControl($container, $column, $record)`

Creates three `TDropDownList` controls (hours, minutes, seconds) for time columns.

### `createDateTimeControl($container, $column, $record)`

Creates a `TDatePicker` plus time dropdowns for datetime columns.

### `createSetControl($container, $column, $record)`

Creates a `TCheckBoxList` for SET-type columns.

### `createEnumControl($container, $column, $record)`

Creates a `TRadioButtonList` for ENUM-type columns.

### `createYearControl($container, $column, $record)`

Creates a `TDropDownList` with years (current-10 to current+10).

## Validator Methods

### `createRequiredValidator($container, $column, $record)`

Creates a `TRequiredFieldValidator` for non-null columns.

### `createTypeValidator($container, $column, $record)`

Creates a `TDataTypeValidator` for type checking.

### `createRangeValidator($container, $column, $record)`

Creates a `TRangeValidator` for numeric range checking.

## Value Retrieval Methods

### `getDefaultControlValue($container, $column, $record)`

Gets value from TTextBox or TCheckBox.

### `getDateTimeValue($container, $column, $record)`

Combines date and time controls into a datetime string.

### `getTimeValue($container, $column, $record)`

Combines hour, minute, second dropdowns into a time string.

### `getSetValue($container, $column, $record)`

Gets comma-separated selected values from CheckBoxList.

### `getEnumValue($container, $column, $record)`

Gets selected value from RadioButtonList.

## See Also

- [TScaffoldInputBase](./TScaffoldInputBase.md)
- `Prado\Web\UI\WebControls\TCheckBox`
- `Prado\Web\UI\WebControls\TTextBox`
- `Prado\Web\UI\WebControls\TDatePicker`
- `Prado\Web\UI\WebControls\TDropDownList`

## Category

ActiveRecord Scaffold InputBuilder
