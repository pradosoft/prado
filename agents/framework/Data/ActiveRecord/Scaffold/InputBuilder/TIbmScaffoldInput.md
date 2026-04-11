# Data/ActiveRecord/Scaffold/InputBuilder/TIbmScaffoldInput

### Directories
[framework](../../../../INDEX.md) / [Data](../../../INDEX.md) / [ActiveRecord](../../INDEX.md) / [Scaffold](../INDEX.md) / [InputBuilder](./INDEX.md) / **`TIbmScaffoldInput`**

## Class Info
**Location:** `framework/Data/ActiveRecord/Scaffold/InputBuilder/TIbmScaffoldInput.php`
**Namespace:** `Prado\Data\ActiveRecord\Scaffold\InputBuilder`

## Overview
`Prado\Data\ActiveRecord\Scaffold\InputBuilder\TIbmScaffoldInput`

IBM DB2-specific scaffold input builder.

Inherits from [`TScaffoldInputCommon`](./TScaffoldInputCommon.md).

## Description

`TIbmScaffoldInput` maps IBM DB2 column types to appropriate Prado scaffold input controls.

## IBM DB2 Type Mappings

| IBM DB2 Type | Control Created |
|--------------|-----------------|
| `date` | `TDatePicker` |
| `time` | Hour/minute/second dropdowns |
| `timestamp` | `TDatePicker` + time dropdowns |
| `smallint`, `integer`, `bigint` | `TTextBox` with integer validation |
| `decimal`, `numeric`, `real`, `float`, `double` | `TTextBox` with float validation |
| `char`, `varchar` | Multiline `TTextBox` |
| Other | Default `TTextBox` |

## Key Methods

### `createControl($container, $column, $record)`

Creates the appropriate control based on IBM DB2 column type.

### `getControlValue($container, $column, $record)`

Gets the control value and converts it to the appropriate type.

## See Also

- [TScaffoldInputCommon](./TScaffoldInputCommon.md)
- [TScaffoldInputBase](./TScaffoldInputBase.md)

## Category

ActiveRecord Scaffold InputBuilder
