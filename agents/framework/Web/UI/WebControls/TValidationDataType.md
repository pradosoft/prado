# Web/UI/WebControls/TValidationDataType

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TValidationDataType`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TValidationDataType.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TValidationDataType defines the enumerable type for the possible data types that a comparison validator can validate upon.

## Key Properties/Methods

- `Integer` - 32-bit signed integer
- `Float` - Double-precision floating point
- `Date` - Date data type
- `String` - String data type
- `convert(string $dataType, int|float|string $value, string $dateFormat = '')` - Converts a value with the rules of the client validators; null when it does not convert. `Integer`: sign and digits. `Float`: sign, digits, `.` fraction, no exponent. `Date`: an int or float is a timestamp (`TDatePicker::getValidationPropertyValue()` returns one), else `TSimpleDateFormatter` with a format, else `strtotime()`. `String`: unchanged. Used by `TCompareValidator` and `TDataTypeValidator` (@since 4.4.0)

## See Also

- [TCompareValidator](./TCompareValidator.md)
- [TValidationDataType](./TValidationDataType.md)
