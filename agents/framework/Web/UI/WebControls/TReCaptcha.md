# TReCaptcha

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TReCaptcha](./TReCaptcha.md)

**Location:** `framework/Web/UI/WebControls/TReCaptcha.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TReCaptcha displays a reCAPTCHA widget (legacy API) that determines if input is entered by a real user. It requires public and private API keys from reCAPTCHA. Only one reCAPTCHA control per page is supported. Validation is performed server-side via the `validate()` method.

## Key Properties/Methods

- `getPublicKey()` / `setPublicKey(string)` - The public API key
- `getPrivateKey()` / `setPrivateKey(string)` - The private API key
- `getThemeName()` / `setThemeName(string)` - reCAPTCHA theme (e.g., 'red', 'white')
- `getLanguage()` / `setLanguage(string)` - Language code
- `getCustomTranslations()` / `setCustomTranslations(array)` - Custom translations
- `validate()` - Validates the reCAPTCHA response server-side
- `regenerateToken()` - Generates a new CAPTCHA token

## See Also

- [TReCaptchaValidator](./TReCaptchaValidator.md)
- [TReCaptcha2](./TReCaptcha2.md)
- [TReCaptcha2Validator](./TReCaptcha2Validator.md)
