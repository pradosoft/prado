# Web/UI/WebControls/TRangeValidator

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TRangeValidator`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TRangeValidator.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TRangeValidator tests whether an input value is within a specified range. It uses MinValue, MaxValue, and DataType properties to validate Integer, Float, Date, String, or StringLength values, and the HTML input values Time, Month, Week, and DateTimeLocal (4.4.0). `Step` checks the HTML `step` rule (4.4.0).

## Key Properties/Methods

- `getMinValue()` / `setMinValue($value)` - Minimum value of validation range
- `getMaxValue()` / `setMaxValue($value)` - Maximum value of validation range
- `getDataType()` / `setDataType($value)` - Data type for comparison (Integer, Float, Date, String, StringLength, Time, Month, Week, DateTimeLocal)
- `getStep()` / `setStep($value)` - Step between valid values, in the data type's unit; `''`, `any`, or a non-positive value checks no step (4.4.0)
- `getStrictComparison()` / `setStrictComparison($value)` - Whether to use strict comparison (< or <=)
- `getDateFormat()` / `setDateFormat($value)` - Date format for date validation
- `getCharset()` / `setCharset($value)` - Charset for string length comparison

## HTML Input Values (4.4.0)

The values a `TTextBox` of the matching `TextMode` posts. MinValue and MaxValue use the same format; another format is invalid, on the server and in `validation3.js`.

| DataType | Format | Number (range and step unit) |
|---|---|---|
| `Time` | `HH:mm[:ss[.sss]]` | seconds since midnight |
| `Month` | `yyyy-MM` | months since 1970-01 |
| `Week` | `yyyy-Www` (ISO 8601; week 53 only in 53-week years) | weeks since 1970-W01 |
| `DateTimeLocal` | `yyyy-MM-ddTHH:mm[:ss[.sss]]` (`T` or space) | seconds since 1970-01-01T00:00 |

Step: `(value - MinValue) / Step` is a whole number; without MinValue the base is 0 of the unit. Integer and Float step in numbers, Date in days (`DateFormat` or `strtotime`), the HTML types in the units above. String and StringLength have no step.

## See Also

- [TBaseValidator](./TBaseValidator.md)
- [TRangeValidationDataType](./TRangeValidationDataType.md)
- [TValidationDataType](./TValidationDataType.md)
