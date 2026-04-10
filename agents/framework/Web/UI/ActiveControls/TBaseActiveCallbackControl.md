# Web/UI/ActiveControls/TBaseActiveCallbackControl

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TBaseActiveCallbackControl`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TBaseActiveCallbackControl.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Common options and functionality for active controls that perform callback requests. Extends [TBaseActiveControl](./TBaseActiveControl.md) with client-side options, validation support, and callback parameter handling.

## Key Properties/Methods

- `getClientSide()` - Returns [TCallbackClientSide](./TCallbackClientSide.md) options
- `getCausesValidation()` / `setCausesValidation($value)` - Trigger validation on callback
- `getValidationGroup()` / `setValidationGroup($value)` - Validator group
- `getCallbackParameter()` / `setCallbackParameter($value)` - Parameter sent with callback
- `getCallbackOptions()` / `setCallbackOptions($value)` - ID of [TCallbackOptions](./TCallbackOptions.md) to clone
- `registerCallbackClientScript($class, $options)` - Register callback JavaScript
- `getJavascript()` - Get JavaScript callback request instance
- `canCauseValidation()` - Check if validation can be triggered

## See Also

- [TBaseActiveControl](./TBaseActiveControl.md), [TCallbackClientSide](./TCallbackClientSide.md), [ICallbackEventHandler](./ICallbackEventHandler.md)
