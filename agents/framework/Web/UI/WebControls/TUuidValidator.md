# Web/UI/WebControls/TUuidValidator

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TUuidValidator`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TUuidValidator.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [TBaseValidator](./TBaseValidator.md)

## Overview
Validates an RFC 9562 UUID, such as `550e8400-e29b-41d4-a716-446655440000`. The hex digits are case-insensitive. Client and server apply the same checks. An empty value passes; pair it with [TRequiredFieldValidator](./TRequiredFieldValidator.md).

## Key Properties

| Property | Default | Description |
|---|---|---|
| `Versions` | `''` | Comma separated versions from 1 to 8; empty allows every version. `getVersionList()` drops out-of-range, non-numeric, and repeated entries. |
| `Strict` | `true` | Only the hyphenated 8-4-4-4-12 form. `false` also accepts the 32 digit form, `{…}` braces, and a `urn:uuid:` prefix (case-insensitive), in combination. |
| `AllowNil` | `false` | Whether the nil UUID (all zeros) is valid. The max UUID (all `f`) is never valid; its version digit is `f`. |

## Mechanics

| Check | Fails when |
|---|---|
| form | the value does not match `CANONICAL_REGEXP`, or, without `Strict`, `COMPACT_REGEXP` after stripping the prefix and braces |
| nil | all 32 digits are 0 and `AllowNil` is false |
| version | digit 13 (the first of the third group) is not 1–8, or not in `Versions` |
| variant | digit 17 (the first of the fourth group) is not 8, 9, a, or b |

- `isValidUuid(string): bool` is public.
- Client: `Prado.WebUI.TUuidValidator` in `validation3.js`; options `Versions` (array), `Strict`, `AllowNil`.

## Accessibility
Inherited from TBaseValidator: the message has `role="alert"`, it is linked to the field through `aria-describedby`, and the field gets `aria-invalid`. An error message with an example UUID shows the expected form.

## Tests

- `tests/unit/Web/UI/WebControls/TUuidValidatorTest.php`
- `tests/js/validator/uuidvalidator.test.js`
- Both run the cases in `tests/js/validator/uuid-cases.json`.
- `tests/playwright/validators/UuidValidatorTestCase.spec.js` with `tests/harness/validators/protected/pages/UuidValidator.page`

## See Also

- [TBaseValidator](./TBaseValidator.md)
- [TRegularExpressionValidator](./TRegularExpressionValidator.md)

**@since 4.4.0**
