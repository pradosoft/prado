# TActiveCustomValidator

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [ActiveControls](./INDEX.md) > [TActiveCustomValidator](./TActiveCustomValidator.md)

**Location:** `framework/Web/UI/ActiveControls/TActiveCustomValidator.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview

Custom validation using server-side OnServerValidate event via callback. Client-side validation function is not supported. Raises OnServerValidate on callback and updates client-side validation state.

## Key Properties/Methods

- `getIsCallback()` - Returns true if validation is during callback
- `setClientValidationFunction($value)` - Not supported, throws TNotSupportedException
- `raiseCallbackEvent($param)` - Raises OnServerValidate then OnCallback
- `setIsValid($value)` - Sets validity with client-side update
- `setErrorMessage($value)` - Sets error message with client-side update
- `getEnableClientScript()` - Always returns true
- `getClientClassName()` - Returns `Prado.WebUI.TActiveCustomValidator`

## See Also

- `TCustomValidator`, [ICallbackEventHandler](./ICallbackEventHandler.md), [TActiveCustomValidatorClientSide](./TActiveCustomValidatorClientSide.md)
