# Web/UI/WebControls/TDataTypeValidator

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TDataTypeValidator`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TDataTypeValidator.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TDataTypeValidator verifies if input data matches a specified data type. Supported types include Integer, Float, Date, and String. For Date type, a custom date format can be specified.

## Key Properties/Methods

- `getDataType()` / `setDataType(TValidationDataType)` - The data type to validate against
- `getDateFormat()` / `setDateFormat(string)` - Date format for Date validation
- `evaluateIsValid()` - Performs server-side validation
- `evaluateDataTypeCheck($value)` - Checks if value matches the data type

## See Also

- [TBaseValidator](./TBaseValidator.md)
- [TValidationDataType](./TValidationDataType.md)
