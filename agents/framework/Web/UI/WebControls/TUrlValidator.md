# Web/UI/WebControls/TUrlValidator

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TUrlValidator`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TUrlValidator.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [TBaseValidator](./TBaseValidator.md)

## Overview
Validates an absolute URL of the form `scheme://host[:port][/path][?query][#fragment]`. It checks the syntax only and makes no network request. An empty value passes; pair it with [TRequiredFieldValidator](./TRequiredFieldValidator.md). Client and server apply the same checks.

## Key Properties

| Property | Default | Description |
|---|---|---|
| `ValidSchemes` | `http,https` | Comma separated schemes, compared case-insensitively. An empty list allows every scheme. `getValidSchemeList()` returns them trimmed, lower case, and unique. |
| `AllowIdn` | `true` | A host with Unicode labels is converted to ASCII under UTS #46 and then checked. `false` fails it. |
| `RequireTld` | `true` | A domain name needs two or more labels and a top-level label of letters or `xn--`, so `http://localhost` fails. IP address hosts are exempt. |

## Mechanics

| Value | Result |
|---|---|
| `''` | valid |
| no `://`, such as `example.com`, `mailto:a@b.c`, `javascript:…` | invalid |
| scheme outside `ValidSchemes` | invalid |
| space or control character anywhere | invalid |
| port above 65535, or `:` without digits | invalid |
| `[…]` host | must be an IPv6 address (`filter_var` / `isIPv6()`) |
| host of digits and dots | must be a dotted-decimal IPv4 address without leading zeros |
| other host | labels of `[a-z0-9_-]`, 1–63 characters, not starting or ending with `-`, 253 characters in all |

- `URL_REGEXP` splits the URL; user information (`user:pass@`) is accepted. `DOMAIN_REGEXP`, `TLD_REGEXP`, and `MAX_DOMAIN_LENGTH` check the host.
- Server: `idn_to_ascii(IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46)` (ext-intl is required). The `u` modifier fails a value of invalid UTF-8.
- Client: `Prado.WebUI.TUrlValidator` in `validation3.js` converts an IDN host with `new URL()`, the WHATWG parser, which applies UTS #46 too. `isIPv4()` and `isIPv6()` are its address checks.
- `isValidUrl(string)` is public for use outside a form.
- Client options: `ValidSchemes` (array), `AllowIdn`, `RequireTld`.

## Accessibility
Inherited from TBaseValidator: the message has `role="alert"`, it is linked to the field through `aria-describedby`, and the field gets `aria-invalid`. A [TTextBox](./TTextBox.md) with `TextMode="Url"` gives a URL keyboard on touch devices.

## Tests

- `tests/unit/Web/UI/WebControls/TUrlValidatorTest.php`
- `tests/js/validator/urlvalidator.test.js`
- Both run the cases in `tests/js/validator/url-cases.json`, so client and server agree.
- `tests/playwright/validators/UrlValidatorTestCase.spec.js` with `tests/harness/validators/protected/pages/UrlValidator.page`: client checks with IDN hosts in each browser, options, server-only validation, and accessibility.

## See Also

- [TBaseValidator](./TBaseValidator.md)
- [TEmailAddressValidator](./TEmailAddressValidator.md)
- [TRegularExpressionValidator](./TRegularExpressionValidator.md)

**@since 4.4.0**
