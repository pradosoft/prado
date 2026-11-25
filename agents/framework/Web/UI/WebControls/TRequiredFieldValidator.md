# Web/UI/WebControls/TRequiredFieldValidator

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TRequiredFieldValidator`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TRequiredFieldValidator.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TRequiredFieldValidator makes the associated input control a required field. Validation fails if the value does not change from the InitialValue upon losing focus. Also works with TListControl and TRadioButton groups.

## Key Properties/Methods

- `getInitialValue()` / `setInitialValue($value)` - Initial value that validation checks against
- `evaluateIsValid()` - Returns true if input changed from initial value

| Target | Valid when |
|---|---|
| `TListControl` | a selected item's value is not exactly `InitialValue` (`01` differs from `1`, as on the client) |
| `TRadioButton` with a `GroupName` | a button of the group is checked, and its value is empty or not `InitialValue` |
| another control | the trimmed value is not the trimmed `InitialValue`, or the value is `true` |

## See Also

- [TBaseValidator](./TBaseValidator.md)
