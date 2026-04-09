# TCaptcha

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TCaptcha](./TCaptcha.md)

**Location:** `framework/Web/UI/WebControls/TCaptcha.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TCaptcha displays a CAPTCHA (token displayed as an image) to determine if input is from a real user. Does not require session or cookie. Note: Does not provide full security against replay attacks; consider using TReCaptcha instead.

## Key Properties/Methods

- `MinTokenLength` / `MaxTokenLength` - Token length range (2-40 chars)
- `CaseSensitive` - Whether comparison is case-sensitive
- `TokenAlphabet` - Characters that may appear in tokens
- `TokenExpiry` - Seconds until token expires (default 600)
- `TestLimit` - Max times a token can be tested (default 5)
- `TokenFontSize` - Font size for token display (20-100)
- `TokenImageTheme` - Theme for token image (0-63)
- `ChangingTokenBackground` - Vary background on postbacks
- `validate()` - Validates user input against token
- `regenerateToken()` - Generates a new token

## See Also

- [TCaptchaValidator](./TCaptchaValidator.md)
- [TReCaptcha](./TReCaptcha.md)
