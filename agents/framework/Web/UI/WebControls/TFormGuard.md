# Web/UI/WebControls/TFormGuard

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TFormGuard`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TFormGuard.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Since:** 4.4.0

## Overview
TFormGuard is a `TBaseValidator` that rejects automated submissions without asking the user anything. It validates the form it sits in: `getValidationTarget()` returns null (the `TCustomValidator` pattern) and `ControlToValidate` is ignored. Client script is off.

It renders, after the validator's error span:
- a honeypot `<input type="text">` named `UniqueID$HoneypotName` inside a `<span aria-hidden="true">` positioned off-screen, with `tabindex="-1"` and `autocomplete="off"`;
- a hidden `UniqueID$ts` stamp: `TSecurityManager::hashData("<time>:<UniqueID>")`.

It implements `IPostBackDataHandler` and calls `registerRequiresPostData()` in `onPreRender`, so `loadPostData()` reads both sub-fields from the full post data on the next postback (sub-fields have no control of their own to receive them).

`evaluateIsValid()` runs once per request. `evaluateFailure()` counts the submission, then reports the first `TFormGuardFailure`:

| Check | Failure |
|---|---|
| honeypot has a value | `Honeypot` |
| stamp missing, altered, or for another UniqueID | `Stamp` |
| elapsed < `MinFillTime` | `TooFast` |
| `MaxFillTime` > 0 and elapsed > `MaxFillTime` | `Expired` |
| count > `RateLimit` in the window | `RateLimited` |

A failed postback re-renders the posted stamp's time, so the fill time counts from the first render.

**Rate limit:** fixed window. Key = `CACHE_KEY_PREFIX . sha256(pagePath|UniqueID|getRateLimitKey()) . ':' . intdiv(now, RateWindow)`, TTL to the window's end. `add()` then `get()`/`set()`; exact only on caches with an atomic add, approximate otherwise (the cache layer has no increment). `getRateLimitKey()` returns `REMOTE_ADDR`; Prado has no proxy trust, so an app behind a proxy overrides it. A RateLimit without a cache throws `cachemoduleid_cache_required`.

## Key Properties/Methods

- `HoneypotName` - honeypot field name (default `website`; letters, digits, `_`, `-`)
- `MinFillTime` - minimum seconds from render to submit (default 3; 0 off)
- `MaxFillTime` - maximum seconds (default 0, off)
- `RateLimit` - submissions per client per window (default 0, off)
- `RateWindow` - window seconds (default 60)
- `CacheModuleID` - from `TCacheModuleIDTrait`
- `FailureReason` - the `TFormGuardFailure` of the last failed evaluation, or null

## Accessibility

Audited in 4.4.0. The honeypot is invisible, removed from the accessibility tree (`aria-hidden` container), and out of the tab order (`tabindex="-1"`), so a person never meets it. Its label says "Leave this field empty" for the case where styles fail to load. The guard adds no other visible content; its error message keeps the validator's `role="alert"`. `tests/playwright/web/AntiBotTestCase.spec.js` checks that the honeypot has no accessible textbox and that Tab skips it.

## See Also

- [TProofOfWork](./TProofOfWork.md)
- [TCaptcha](./TCaptcha.md)
- [TCustomValidator](./TCustomValidator.md)
- [TCacheModuleIDTrait](../../../Caching/TCacheModuleIDTrait.md)
