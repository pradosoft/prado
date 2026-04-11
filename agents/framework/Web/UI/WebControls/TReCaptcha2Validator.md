# Web/UI/WebControls/TReCaptcha2Validator

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TReCaptcha2Validator`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TReCaptcha2Validator.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TReCaptcha2Validator validates user input against a TReCaptcha2 control. It performs server-side validation of the reCAPTCHA response token. The validation fails if the user does not pass the humanity test.

## Key Properties/Methods

- `getCaptchaControl()` - Gets the associated TReCaptcha2 control
- `getEnableClientScript()` - Always returns true, client script enabled
- `evaluateIsValid()` - Server-side validation logic

## See Also

- [TReCaptcha2](./TReCaptcha2.md)
- [TBaseValidator](./TBaseValidator.md)
