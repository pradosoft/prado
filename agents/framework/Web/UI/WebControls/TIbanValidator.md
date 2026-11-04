# Web/UI/WebControls/TIbanValidator

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TIbanValidator`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TIbanValidator.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [TBaseValidator](./TBaseValidator.md)

## Overview
Validates an International Bank Account Number (ISO 13616), such as `DE89 3704 0044 0532 0130 00`. Spaces are ignored and letters are compared upper case, so the print format and pasted lower case pass. Client and server apply the same checks. The checks confirm that an IBAN is well formed, not that the account exists. An empty value passes; pair it with [TRequiredFieldValidator](./TRequiredFieldValidator.md).

## Key Properties

| Property | Default | Description |
|---|---|---|
| `Countries` | `''` | Comma separated country codes, compared upper case; empty allows every country of `COUNTRY_FORMATS`. Unknown codes are dropped. `getCountryFormats()` returns the allowed formats. |

## Mechanics

| Check | Fails when |
|---|---|
| country | the first 2 characters are not a key of the allowed formats |
| check digits | characters 3–4 are not digits |
| BBAN | the rest does not match the country format, such as `18n` for DE or `4a,10n` for NL; the length follows from the format |
| checksum | `mod97(BBAN . country . check digits)` is not 1 (ISO 7064 mod 97-10; A = 10 … Z = 35) |

- `COUNTRY_FORMATS`: 89 countries from the SWIFT IBAN Registry, Release 99 (December 2024), as `n` digits, `a` letters, and `c` letters or digits. The IBAN length is 4 plus the runs. Aspirational codes, such as `DZ`, are not included.
- `isValidIban(string)`, `bbanPattern(string)`, and `mod97(string)` are public; the last two are static. `mod97()` folds digit by digit, so no big integers are needed.
- Client: `Prado.WebUI.TIbanValidator` in `validation3.js` receives `CountryFormats` from the server, so the table has one source. The option holds only the allowed countries.
- Hyphens and tabs fail; only spaces are ignored, anywhere in the value.

## Accessibility
Inherited from TBaseValidator: the message has `role="alert"`, it is linked to the field through `aria-describedby`, and the field gets `aria-invalid`. An error message with an example IBAN shows the expected form.

## Tests

- `tests/unit/Web/UI/WebControls/TIbanValidatorTest.php`: the table's shape and spot lengths, `mod97()`, `bbanPattern()`, `Countries`.
- `tests/js/validator/ibanvalidator.test.js`
- Both run the cases in `tests/js/validator/iban-cases.json`, built on the registry example IBANs.
- `tests/playwright/validators/IbanValidatorTestCase.spec.js` with `tests/harness/validators/protected/pages/IbanValidator.page`

## See Also

- [TBaseValidator](./TBaseValidator.md)
- [TLuhnValidator](./TLuhnValidator.md)

**@since 4.4.0**
