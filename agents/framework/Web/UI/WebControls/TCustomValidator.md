# Web/UI/WebControls/TCustomValidator

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TCustomValidator`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TCustomValidator.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TCustomValidator performs user-defined validation on both server-side and client-side. Supports custom validation functions.

## Key Properties/Methods

- `ClientValidationFunction` - Name of client-side JS validation function
- `onServerValidate()` - Server-side validation handler event
- `evaluateIsValid()` - Invokes OnServerValidate event
- `getValidationTarget()` - Returns null when `ControlToValidate` is empty, and the server validates an empty value. A `ControlToValidate` that names no control throws `TConfigurationException` (`basevalidator_controltovalidate_invalid`), as in `TBaseValidator`. Without a target, the client options carry no `ControlToValidate` or `ControlType`, and `FocusElementID` is empty.

## See Also

- [TBaseValidator](./TBaseValidator.md)
