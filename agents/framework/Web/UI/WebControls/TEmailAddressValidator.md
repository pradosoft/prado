# Web/UI/WebControls/TEmailAddressValidator

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TEmailAddressValidator`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TEmailAddressValidator.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TEmailAddressValidator validates whether a value is a valid email address of at most 254 bytes. Can optionally check MX records.

## Key Properties/Methods

- `CheckMXRecord` - Whether to check DNS MX records (default false); the domain is the text after the last `@`. Skipped when `checkdnsrr()` is unavailable.
- `EMAIL_REGEXP` - Regular expression for email validation; `RegularExpression` replaces it
- `hasMxRecord(string $domain): bool` - Protected `checkdnsrr()` MX lookup; a test subclass overrides it (@since 4.4.0)

## See Also

- [TRegularExpressionValidator](./TRegularExpressionValidator.md)
