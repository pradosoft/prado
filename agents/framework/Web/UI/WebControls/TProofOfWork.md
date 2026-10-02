# Web/UI/WebControls/TProofOfWork

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TProofOfWork`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TProofOfWork.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Since:** 4.4.0

## Overview
TProofOfWork makes the browser spend CPU before a form posts. It needs no perception or input, so a vision model gains nothing; the cost per submission is what deters bulk spam. Validate it with [TProofOfWorkValidator](./TProofOfWorkValidator.md). The scheme follows ALTCHA: a bounded search gives a predictable cost, unlike leading-zero puzzles.

**Challenge (per render, stateless):** `salt = hex(12 random bytes) . '?expires=' . (now + ChallengeExpiry)`; `challenge = sha256(salt . random_int(0, Complexity))`; `signature = HMAC-SHA256(challenge . '|' . UniqueID, TSecurityManager::getValidationKey())`. Sent to the client in the options as `{algorithm, challenge, salt, maxnumber, signature}`.

**Verification (`validate()`, once per request):** JSON `{challenge, salt, signature, number}` from the hidden field → signature matches (`hash_equals`) → `expires` in the salt not passed → `sha256(salt . number) === challenge` → `claimCacheKey(CACHE_KEY_PREFIX . challenge, expires - now)`. The salt cannot be altered: it is bound by the challenge hash, which the signature covers. A cache is required; `onPreRender` throws `cachemoduleid_cache_required` without one.

**Client (`controls/proofofwork.js`, package `proofofwork`):** `Prado.WebUI.TProofOfWork` starts per `StartMode` (`TProofOfWorkStartMode`: `Focus` default, `Load`, `Submit`). It solves in a Worker loaded from `controls/proofofwork-solver.js` (URL `getPradoScriptAssetUrl() . SOLVER_SCRIPT`), falling back to `Prado.ProofOfWorkSolver.solveAsync()` on the main thread in 5000-number chunks when a Worker cannot start (CSP `worker-src`, no Worker). The solver file is dual-mode: Worker `onmessage`, or `Prado.ProofOfWorkSolver` as a page script. It uses a pure-JS SHA-256 because `crypto.subtle` needs a secure context. About 1.1 M hashes/s on a 2026 desktop; the default 500000 averages ~0.2 s.

**Submission hold:** Prado's postbacks are synchronous; nothing awaits a Promise. The control listens for the form `submit` event and cancels it until solved. Prado's `Prado.PostBack` dispatches a synthetic (untrusted) submit then calls `form.submit()`; a held synthetic submit resumes with `form.submit()` (the `PRADO_POSTBACK_*` inputs are already in the form). A held native submit resumes with `form.requestSubmit(submitter)` behind a re-entry flag. A Prado button can fire both events for one click; `pending`/`waiting` resume once.

**Callbacks:** the control registers `holdCallback()` as a `Prado.CallbackRequestManager` send gate (`ajax3.js`); a callback with `CausesValidation` waits for the solution before its inputs are serialized. Each callback runs `onPreRender`, so the response re-creates the client control with a new challenge. `KeepSolved` is true when the callback did not call `validate()`: the client keeps the unused solution in the field. After a validating callback it is false, the field is cleared, and the next validating callback or submit solves the new challenge. A replaced instance (`registered` false) never writes its late solution.

**Layout:** the status span holds `&nbsp;` before solving. Without it, focusing the submit button inserted "Verifying…" above it and moved the button between mousedown and mouseup, so the click never landed.

## Key Properties/Methods

- `Complexity` - largest secret number, 1000-10000000 (default 500000)
- `ChallengeExpiry` - seconds (default 1800; minimum 60)
- `StartMode` - `TProofOfWorkStartMode` (default `Focus`)
- `VerifyingText`, `VerifiedText`, `FailedText`, `NoScriptText` - localized defaults
- `CacheModuleID` - from `TCacheModuleIDTrait`
- `validate()` - verifies and claims the posted solution

## Accessibility

Audited in 4.4.0. The control is a `role="status"` live region (polite) with `aria-busy` true while solving; status text announces "Verifying…", "Verified", or the failure. It asks nothing of the user, so it is the alternative for an image CAPTCHA under WCAG 1.1.1. The work is short and needs no timing response (2.2.1). Without JavaScript the `<noscript>` text explains the requirement. `tests/playwright/web/AntiBotTestCase.spec.js` checks the role, `aria-busy`, the status text, and that the submit button does not move.

## See Also

- [TProofOfWorkValidator](./TProofOfWorkValidator.md)
- [TFormGuard](./TFormGuard.md)
- [TCaptcha](./TCaptcha.md)
- [TCacheModuleIDTrait](../../../Caching/TCacheModuleIDTrait.md)
