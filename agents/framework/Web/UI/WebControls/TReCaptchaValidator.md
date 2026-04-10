# Web/UI/WebControls/TReCaptchaValidator

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TReCaptchaValidator`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TReCaptchaValidator.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TReCaptchaValidator validates user input against a TReCaptcha control. It performs server-side validation of the reCAPTCHA response. Note that calling `validate()` invalidates the token, so it should only be called once per submission.

## Key Properties/Methods

- `getCaptchaControl()` - Gets the associated TReCaptcha control
- `getEnableClientScript()` - Always returns true, client script enabled
- `evaluateIsValid()` - Server-side validation logic

## See Also

- [TReCaptcha](./TReCaptcha.md)
- [TBaseValidator](./TBaseValidator.md)
