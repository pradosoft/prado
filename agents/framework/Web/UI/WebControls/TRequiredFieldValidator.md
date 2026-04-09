# TRequiredFieldValidator

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TRequiredFieldValidator](./TRequiredFieldValidator.md)

**Location:** `framework/Web/UI/WebControls/TRequiredFieldValidator.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TRequiredFieldValidator makes the associated input control a required field. Validation fails if the value does not change from the InitialValue upon losing focus. Also works with TListControl and TRadioButton groups.

## Key Properties/Methods

- `getInitialValue()` / `setInitialValue($value)` - Initial value that validation checks against
- `evaluateIsValid()` - Returns true if input changed from initial value

## See Also

- [TBaseValidator](./TBaseValidator.md)
