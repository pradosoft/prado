# Web/UI/WebControls/TCaptcha

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TCaptcha`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TCaptcha.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TCaptcha extends `TImage` and displays a token as a distorted image. `TCaptchaValidator` compares the user's input with the token. Current vision models read the image, so it stops only simple bots; pair it with [TFormGuard](./TFormGuard.md) and [TProofOfWork](./TProofOfWork.md).

**Token:** `hash2string(HMAC-SHA256(PublicKey, PrivateKey))` truncated to the token length. `PublicKey` is random per token and lives in ViewState. `PrivateKey` lives in `captcha_key.php`, written beside the published `captcha.php` and never sent to the client.

**Image:** `onPreRender` publishes `assets/captcha.php` and sets `ImageUrl` to it with `?options=`: base64 of `HMAC-SHA256(options, PrivateKey)` (64 hex) followed by the serialized options. The script recomputes the token; `generateToken()`/`hash2string()` in `captcha.php` must stay identical to TCaptcha's. See [assets/INDEX.md](./assets/INDEX.md).

**Single use (4.4.0):** `validate()` claims `CACHE_KEY_PREFIX . PublicKey` with `TCacheModuleIDTrait::claimCacheKey()` after a match, for the token's remaining lifetime (`SINGLE_USE_TTL`, 7 days, when `TokenExpiry` < 1). A replayed page state fails and regenerates the token. A claimed token regenerates in `onPreRender`, so a re-rendered page shows a new image. Repeated `validate()` calls in one request return the same result. Without a cache, a warning is logged and validation passes (BC).

## Key Properties/Methods

- `MinTokenLength` / `MaxTokenLength` - Token length range (2-40 chars); the length is chosen with `random_int()` and kept in ViewState
- `CaseSensitive` - Whether comparison is case-sensitive
- `TokenAlphabet` - Characters that may appear in tokens
- `TokenExpiry` - Seconds until token expires (default 600)
- `TestLimit` - Max times a token can be tested (default 5)
- `SingleUse` - A solved token passes in one request only (default true) @since 4.4.0
- `CacheModuleID` - Cache for single-use claims; empty uses the primary cache (from `TCacheModuleIDTrait`) @since 4.4.0
- `AlternateText` - Defaults to localized "CAPTCHA image: type the characters shown" @since 4.4.0
- `TokenFontSize` - Font size for token display (20-100)
- `TokenImageTheme` - Theme bits for the image (0-63)
- `ChangingTokenBackground` - Vary background on postbacks
- `validate($input)` - Compares with `hash_equals()`, then claims the token
- `regenerateToken()` - Generates a new token

## Accessibility

Audited in 4.4.0. The image now has a default alt text that identifies its purpose (WCAG 1.1.1). An image CAPTCHA has no audio or text alternative, so 1.1.1 also asks for another way to pass; `TProofOfWork` needs no perception and serves that role on the same form. The functional test `tests/playwright/web/AntiBotTestCase.spec.js` checks the alt text and that the image loads.

## See Also

- [TCaptchaValidator](./TCaptchaValidator.md)
- [TFormGuard](./TFormGuard.md)
- [TProofOfWork](./TProofOfWork.md)
- [TCacheModuleIDTrait](../../../Caching/TCacheModuleIDTrait.md)
- [TReCaptcha2](./TReCaptcha2.md)
