# Web/UI/WebControls/TCaptchaValidator

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TCaptchaValidator`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TCaptchaValidator.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TCaptchaValidator validates the input in `ControlToValidate` against the TCaptcha named by `CaptchaControl`. The server calls `TCaptcha::validate()`, which also claims a single-use token.

The client class `Prado.WebUI.TCaptchaValidator` (`validator/validation3.js`) checks only that the input is not empty. Before 4.4.0 the client received `TokenHash`, the sum of the token's character codes, which leaked the answer; it is no longer sent. A token length check was rejected because a claimed token regenerates in TCaptcha's `onPreRender`, which can run after the validator builds its client options.

## Key Properties/Methods

- `CaptchaControl` - ID path of the TCaptcha control to validate
- `evaluateIsValid()` - Validates input against CAPTCHA token

## See Also

- [TCaptcha](./TCaptcha.md)
- [TBaseValidator](./TBaseValidator.md)
