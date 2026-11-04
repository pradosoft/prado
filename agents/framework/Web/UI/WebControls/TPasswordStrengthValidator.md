# Web/UI/WebControls/TPasswordStrengthValidator

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TPasswordStrengthValidator`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TPasswordStrengthValidator.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [TBaseValidator](./TBaseValidator.md)

## Overview
Validates that a password reaches `MinStrength`, a [TPasswordStrength](./TPasswordStrength.md) level estimated from its characters. Optionally it also looks the password up in the Have I Been Pwned Pwned Passwords range API. An empty value passes; pair it with [TRequiredFieldValidator](./TRequiredFieldValidator.md).

## Key Properties

| Property | Default | Description |
|---|---|---|
| `MinStrength` | `Medium` | Minimum `TPasswordStrength` level. |
| `CheckCompromised` | `false` | Look the password up in Pwned Passwords (server only). |
| `CompromisedThreshold` | `1` | Breach occurrences at which the password fails; at least 1. |
| `CompromisedApiUrl` | `PWNED_PASSWORDS_URL` | Range API URL; the 5 character hash prefix is appended. A self-hosted mirror can replace it. |
| `CompromisedTimeout` | `5` | Request timeout in seconds of the default client. |
| `HttpClient` | `THttpClient::create()` | The transport; not saved in the page state. Tests inject a stub. |

## Strength estimate

`estimateEntropy(string): float` and `estimateStrength(string): string` are public and static.

- The characters are Unicode code points; invalid UTF-8 counts bytes.
- The pool is the sum of the classes present (`CLASS_SIZES`): lower 26, upper 26, digit 10, other ASCII 33, non-ASCII 100.
- Each distinct character adds `log2(pool)` bits. Each repeated character adds `log2(distinct)` bits.
- `STRENGTH_ENTROPY` maps the levels: VeryWeak 0, Weak 40, Medium 60, Strong 80, VeryStrong 100.

| Password | Bits | Level |
|---|---|---|
| `password` | 35.7 | VeryWeak |
| `Password1` | 50.6 | Weak |
| `Password1!` | 62.3 | Medium |
| `kX9#mQ2$vL7!pZ` | 92.0 | Strong |
| `correct horse battery staple` | 132.0 | VeryStrong |

The estimate has no dictionary and no keyboard-pattern detection, so `Password1!` rates Medium. `CheckCompromised` catches common passwords like it.

## Compromised check

- `isCompromised(string): bool` sends `GET {CompromisedApiUrl}{first 5 hex of SHA-1}` with `Add-Padding: true` and a `PRADO/<version>` user agent. The suffix matches case-insensitively; padding entries have count 0.
- It runs after the strength check passes, so a weak password makes no request.
- A transport failure (`THttpClientException`, including no available transport) or a non-2xx status logs `Prado::warning` and passes. An outage never blocks the form.
- The password and its full hash never leave the server, and the log message carries neither.

## Client side
`Prado.Validation.estimatePasswordEntropy()` in `validation3.js` repeats `estimateEntropy()` with `Array.from()` code points and `Math.log2()`; `Prado.WebUI.TPasswordStrengthValidator` and [TPasswordStrengthMeter](./TPasswordStrengthMeter.md) both use it. The only option is `MinEntropy`, the bits of `MinStrength`. The compromised check has no client side.

## Accessibility
Inherited from TBaseValidator: the message has `role="alert"`, it is linked to the field through `aria-describedby`, and the field gets `aria-invalid`. The error message should state what a valid password needs. A `TTextBox` with `TextMode="Password"` and `Attributes.autocomplete="new-password"` lets password managers suggest a strong password.

## Tests

- `tests/unit/Web/UI/WebControls/TPasswordStrengthValidatorTest.php`: a stub `THttpClient` covers the compromised check.
- `tests/js/validator/passwordstrengthvalidator.test.js`
- Both run the cases in `tests/js/validator/password-cases.json`, so client and server estimate the same entropy.
- `tests/playwright/validators/PasswordStrengthValidatorTestCase.spec.js` with `tests/harness/validators/protected/pages/PasswordStrengthValidator.page`: client checks, server-only checks, and accessibility. The compromised check is not exercised, since the single-threaded test server cannot answer its own request.

## See Also

- [TPasswordStrengthMeter](./TPasswordStrengthMeter.md)
- [TPasswordStrength](./TPasswordStrength.md)
- [TBaseValidator](./TBaseValidator.md)
- [THttpClient](../../../IO/HttpClient/INDEX.md)

**@since 4.4.0**
