# Web/UI/WebControls/TReCaptcha2Validator

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TReCaptcha2Validator`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TReCaptcha2Validator.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TReCaptcha2Validator validates the TReCaptcha2 named by `ControlToValidate` (not `CaptchaControl`). It calls `TReCaptcha2::validate()`, which verifies the response token with Google's siteverify, and caches the result in `_isvalid` for the request.

## Key Properties/Methods

- `getCaptchaControl()` - Gets the associated TReCaptcha2 control
- `getEnableClientScript()` - Always returns true, client script enabled
- `evaluateIsValid()` - Server-side validation logic

## See Also

- [TReCaptcha2](./TReCaptcha2.md)
- [TBaseValidator](./TBaseValidator.md)
