# Web/UI/WebControls/TLuhnValidator

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TLuhnValidator`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TLuhnValidator.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [TBaseValidator](./TBaseValidator.md)

## Overview
Validates the Luhn (mod 10) checksum of payment card numbers, IMEI numbers, and other identification numbers. The checksum catches a single mistyped digit and most transpositions of adjacent digits; it does not prove the number exists. Client and server apply the same checks. An empty value passes; pair it with [TRequiredFieldValidator](./TRequiredFieldValidator.md).

## Key Properties

| Property | Default | Description |
|---|---|---|
| `AllowSeparators` | `false` | Ignore spaces and hyphens, such as in `4111 1111 1111 1111`. Other characters still fail. |

## Mechanics

- `isValidLuhn(string): bool` is public and static: 2 or more ASCII digits only; from the rightmost digit, every second digit is doubled (minus 9 above 9); the sum is a multiple of 10.
- The value is not trimmed; a leading space fails without `AllowSeparators`.
- Client: `Prado.WebUI.TLuhnValidator` in `validation3.js`; option `AllowSeparators`.
- Length and issuer rules, such as 16 digits for a card brand, belong to a [TRegularExpressionValidator](./TRegularExpressionValidator.md) on the same field.

## Accessibility
Inherited from TBaseValidator: the message has `role="alert"`, it is linked to the field through `aria-describedby`, and the field gets `aria-invalid`. Give the text box `Attributes.inputmode="numeric"` for a numeric keyboard, and `Attributes.autocomplete="cc-number"` for a card number.

## Tests

- `tests/unit/Web/UI/WebControls/TLuhnValidatorTest.php`
- `tests/js/validator/luhnvalidator.test.js`
- Both run the cases in `tests/js/validator/luhn-cases.json`.
- `tests/playwright/validators/LuhnValidatorTestCase.spec.js` with `tests/harness/validators/protected/pages/LuhnValidator.page`

## See Also

- [TBaseValidator](./TBaseValidator.md)

**@since 4.4.0**
