# Web/UI/WebControls/TListControlValidator

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TListControlValidator`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TListControlValidator.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TListControlValidator validates selection count and values in list controls that allow multiple selection (TListBox, TCheckBoxList, etc.).

## Key Properties/Methods

- `MinSelection` - Minimum required selections
- `MaxSelection` - Maximum allowed selections
- `RequiredSelections` - Comma-separated list of required values
- `evaluateIsValid()` - Validates selection count and required values
- `getValidationListControl()` - Returns the `TListControl` target; the server validation and the client options both throw `TConfigurationException` (`listcontrolvalidator_invalid_control`) for any other control (@since 4.4.0)

## See Also

- [TBaseValidator](./TBaseValidator.md)
- [TListControl](./TListControl.md)
