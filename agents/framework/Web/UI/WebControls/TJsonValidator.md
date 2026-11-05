# Web/UI/WebControls/TJsonValidator

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TJsonValidator`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TJsonValidator.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [TBaseValidator](./TBaseValidator.md)

## Overview
Validates JSON text (RFC 8259): any value, including a scalar such as `42` or `"text"`, with optional surrounding whitespace. It has no properties. Client and server apply the same checks. An empty value passes; pair it with [TRequiredFieldValidator](./TRequiredFieldValidator.md).

## Mechanics

| Value | Result |
|---|---|
| `{"a":1}`, `[1,2]`, `42`, `"text"`, `true`, `null`, ` {} ` | valid |
| `{"a":1,"a":2}` (duplicate keys), `1e400` | valid |
| whitespace only, trailing comma, `'single'`, unquoted key, `01`, `.5`, `NaN`, BOM, raw tab in a string | invalid |
| `"\ud800"` (unpaired surrogate escape) | invalid |
| 512 or more nested arrays and objects | invalid |
| invalid UTF-8 (server) | invalid |

- Server: `isValidJson(string): bool` (public) uses `json_validate($value, MAX_DEPTH)` on PHP 8.3+, else `json_decode()` with `json_last_error()`. `MAX_DEPTH` is 512, PHP's default; it allows 511 nested containers.
- Client: `Prado.WebUI.TJsonValidator` parses with `JSON.parse()`. A reviver rejects keys and strings with a lone surrogate (`/\p{Cs}/u`), because JavaScript accepts them and PHP does not. `nestingDepth()` counts brackets outside strings and requires fewer than the `MaxDepth` option.

## Accessibility
Inherited from TBaseValidator: the message has `role="alert"`, it is linked to the field through `aria-describedby`, and the field gets `aria-invalid`.

## Tests

- `tests/unit/Web/UI/WebControls/TJsonValidatorTest.php`
- `tests/js/validator/jsonvalidator.test.js`
- Both run the cases in `tests/js/validator/json-cases.json`; the depth boundary is tested on both sides.
- `tests/playwright/validators/JsonValidatorTestCase.spec.js` with `tests/harness/validators/protected/pages/JsonValidator.page`

## See Also

- [TBaseValidator](./TBaseValidator.md)

**@since 4.4.0**
