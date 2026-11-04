# Web/UI/WebControls/TUnicodeValidator

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TUnicodeValidator`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TUnicodeValidator.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [TBaseValidator](./TBaseValidator.md)

## Overview
Rejects suspicious Unicode in user names and other identifiers people compare by eye, following the UTS #39 security mechanisms through the intl `Spoofchecker`. It validates on the server only. An empty value passes; pair it with [TRequiredFieldValidator](./TRequiredFieldValidator.md).

## Key Properties

| Property | Default | Fails the value when |
|---|---|---|
| `CheckInvisible` | `true` | a `\p{Cc}` control or `\p{Cf}` format character (zero-width space and joiners, bidi overrides, soft hyphen, BOM), U+034F, a Hangul filler (U+115F, U+1160, U+3164, U+FFA0), or the same nonspacing mark twice in a row (Spoofchecker `INVISIBLE`) |
| `CheckMixedNumbers` | `true` | digits of different numbering systems, such as `١2` (`CHECK_MIXED_NUMBERS`, `1 << 7`; ICU 58+) |
| `CheckHiddenOverlay` | `true` | a combining mark hidden in a base character that shows it, such as `i` + U+0307 (`CHECK_HIDDEN_OVERLAY`, `1 << 8`; ICU 62+) |
| `RestrictionLevel` | `ModeratelyRestrictive` | its scripts exceed the [TUnicodeRestrictionLevel](./TUnicodeRestrictionLevel.md); `Unrestricted` skips the check |
| `Locales` | `''` | a character is outside the scripts of these comma separated locales (Spoofchecker `CHAR_LIMIT`) |

Invalid UTF-8 always fails.

## Mechanics

- `isSuspicious(string): bool` is public. It checks UTF-8 with `preg_match('//u')`, then `INVISIBLE_REGEXP`, then a Spoofchecker from `createSpoofchecker()`. That method returns null when no Spoofchecker check is enabled.
- The `CHECK_*` constants hold the ICU check bits, because PHP before 8.4 lacks `Spoofchecker::MIXED_NUMBERS` and `HIDDEN_OVERLAY`. A check is skipped on an ICU older than it needs, since ICU rejects unknown bits.
- The Spoofchecker does not flag a lone zero-width space at any restriction level, which is why `INVISIBLE_REGEXP` exists.
- Confusable look-alikes of other names (`Spoofchecker::areConfusable()`) need the other name; that is an application check, such as at sign-up.

| Value | Default result | Check |
|---|---|---|
| `José Müller`, `Ελληνικά`, `日本語テキスト`, `abc日本`, `hi😀` | valid | |
| `pаypal` (Cyrillic а) | invalid | restriction level |
| `abc​def` (U+200B), `file‮gpj.exe` (U+202E), tab | invalid | invisible |
| `١2` | invalid | mixed numbers |

## Client side
`Prado.WebUI.TUnicodeValidator` always passes, so a rejected value is posted again and the server decides each time.

## Accessibility
Inherited from TBaseValidator: the message has `role="alert"` and is linked to the field through `aria-describedby`. A failure renders `aria-invalid="true"` on the field from the server (TBaseValidator `updateControlAria()`, 4.4.0); the client validator that failed takes over the attribute (`IsValid: false` option, `claimServerAria()`) and clears it when it passes.

## Tests

- `tests/unit/Web/UI/WebControls/TUnicodeValidatorTest.php`
- `tests/js/validator/unicodevalidator.test.js`
- `tests/playwright/validators/UnicodeValidatorTestCase.spec.js` with `tests/harness/validators/protected/pages/UnicodeValidator.page`

## See Also

- [TUnicodeRestrictionLevel](./TUnicodeRestrictionLevel.md)
- [TBaseValidator](./TBaseValidator.md)

**@since 4.4.0**
