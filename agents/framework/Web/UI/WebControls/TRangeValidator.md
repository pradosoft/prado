# Web/UI/WebControls/TRangeValidator

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TRangeValidator`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TRangeValidator.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TRangeValidator tests whether an input value is within a specified range. It uses MinValue, MaxValue, and DataType properties to validate Integer, Float, Date, String, or StringLength values.

## Key Properties/Methods

- `getMinValue()` / `setMinValue($value)` - Minimum value of validation range
- `getMaxValue()` / `setMaxValue($value)` - Maximum value of validation range
- `getDataType()` / `setDataType($value)` - Data type for comparison (Integer, Float, Date, String, StringLength)
- `getStrictComparison()` / `setStrictComparison($value)` - Whether to use strict comparison (< or <=)
- `getDateFormat()` / `setDateFormat($value)` - Date format for date validation
- `getCharset()` / `setCharset($value)` - Charset for string length comparison

## See Also

- [TBaseValidator](./TBaseValidator.md)
- [TRangeValidationDataType](./TRangeValidationDataType.md)
- [TValidationDataType](./TValidationDataType.md)
