# TCompareValidator

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TCompareValidator](./TCompareValidator.md)

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

## See Also

- [TBaseValidator](./TBaseValidator.md)
