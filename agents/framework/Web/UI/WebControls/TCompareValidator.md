# Web/UI/WebControls/TCompareValidator

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TCompareValidator`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TCompareValidator.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TCompareValidator compares a value against another input control value or a constant value. Supports Integer, Float, Date, and String data types.

## Key Properties/Methods

- `ControlToCompare` - ID path of control to compare against
- `ValueToCompare` - Constant value to compare against
- `DataType` - Data type for comparison (Integer, Float, Date, String)
- `Operator` - Comparison operation (Equal, NotEqual, GreaterThan, etc.)
- `DateFormat` - Date format for date validation
- `evaluateIsValid()` - Performs the comparison validation
- `getComparisonValues($value1, $value2)` - Converts both values with `TValidationDataType::convert()`; a value that does not convert is null

## Patterns & Gotchas

The server and the client apply the same rules (4.4.0):

| Case | Result |
|---|---|
| Input is empty | valid |
| Input does not convert to `DataType` | invalid |
| Value to compare (or an empty `ControlToCompare`) does not convert | valid |
| `String` | exact comparison in byte order; `1e3` is not `1000`, `9` is greater than `10` |
| `Date` without `DateFormat` | any date `strtotime()` parses (client: `Date.SimpleParse()` with the browser `Date` parser), before 1970 included |

## See Also

- [TBaseValidator](./TBaseValidator.md)
