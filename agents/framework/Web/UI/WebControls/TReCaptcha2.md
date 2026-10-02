# Web/UI/WebControls/TReCaptcha2

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TReCaptcha2`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TReCaptcha2.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TReCaptcha2 displays the Google reCAPTCHA v2 widget with callback support. It extends TActivePanel and supports AJAX callbacks. This is the modern replacement for TReCaptcha with better user experience and callback event handling.

**Verification (4.4.0):** `validate()` POSTs `secret`, `response`, and `remoteip` (the remote address, when known) to `VERIFY_URL` (`https://www.google.com/recaptcha/api/siteverify`) through `HttpClient` (`THttpClient::create()` by default: cURL, else PHP streams) and passes only on a JSON `"success": true`. It fails closed: an empty token or SecretKey sends nothing; a `THttpClientException` logs a warning; a non-2xx or unreadable answer fails. The result is kept for the request because Google accepts a token once (`timeout-or-duplicate`). `getVerifyResult()` exposes the decoded answer (`hostname`, `challenge_ts`, `error-codes`) for app-level checks such as the hostname. Before 4.4.0 any non-empty response passed.

`remoteip` is `REMOTE_ADDR`; behind a proxy it is the proxy's address, which Google treats as an optional signal.

## Key Properties/Methods

- `getSiteKey()` / `setSiteKey(string)` - The site key for reCAPTCHA
- `getSecretKey()` / `setSecretKey(string)` - The secret key for server-side validation
- `getTheme()` / `setTheme(string)` - Widget theme ('light' or 'dark')
- `getType()` / `setType(string)` - CAPTCHA type ('image' or 'audio')
- `getSize()` / `setSize(string)` - Widget size ('normal' or 'compact')
- `getTabIndex()` / `setTabIndex(int)` - Tab index for accessibility
- `getCaptchaResponse()` / `setCaptchaResponse(string)` - The reCAPTCHA response token set by the widget callback
- `HttpClient` - The `THttpClient` that posts to siteverify (@since 4.4.0)
- `getVerifyResult()` - Google's decoded siteverify answer for this request (@since 4.4.0)
- `reset()` - Resets the reCAPTCHA widget
- `validate()` - Verifies the response with siteverify
- `onCallback($param)` - Event raised on successful callback
- `onCallbackExpired($param)` - Event raised when token expires

## See Also

- [TReCaptcha](./TReCaptcha.md)
- [TReCaptcha2Validator](./TReCaptcha2Validator.md)
- [TActivePanel](../ActiveControls/TActivePanel.md)
