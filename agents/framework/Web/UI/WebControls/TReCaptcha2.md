# Web/UI/WebControls/TReCaptcha2

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TReCaptcha2`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TReCaptcha2.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TReCaptcha2 displays the Google reCAPTCHA v2 widget with callback support. It extends TActivePanel and supports AJAX callbacks. This is the modern replacement for TReCaptcha with better user experience and callback event handling.

## Key Properties/Methods

- `getSiteKey()` / `setSiteKey(string)` - The site key for reCAPTCHA
- `getSecretKey()` / `setSecretKey(string)` - The secret key for server-side validation
- `getTheme()` / `setTheme(string)` - Widget theme ('light' or 'dark')
- `getType()` / `setType(string)` - CAPTCHA type ('image' or 'audio')
- `getSize()` / `setSize(string)` - Widget size ('normal' or 'compact')
- `getTabIndex()` / `setTabIndex(int)` - Tab index for accessibility
- `getResponse()` / `setResponse(string)` - The reCAPTCHA response token
- `reset()` - Resets the reCAPTCHA widget
- `validate()` - Validates the response
- `onCallback($param)` - Event raised on successful callback
- `onCallbackExpired($param)` - Event raised when token expires

## See Also

- [TReCaptcha](./TReCaptcha.md)
- [TReCaptcha2Validator](./TReCaptcha2Validator.md)
- [TActivePanel](./TActivePanel.md)
