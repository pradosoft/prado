# Web/UI/WebControls/TRegularExpressionValidator

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TRegularExpressionValidator`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TRegularExpressionValidator.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TRegularExpressionValidator validates whether the value of an associated input control matches a specified regular expression pattern. Validation succeeds if the input is empty (use TRequiredFieldValidator to require non-empty input).

## Key Properties/Methods

- `getRegularExpression()` / `setRegularExpression(string)` - The regex pattern
- `getPatternModifiers()` / `setPatternModifiers(string)` - Server-side PCRE modifiers
- `getClientSidePatternModifiers()` / `setClientSidePatternModifiers(string)` - Client-side modifiers (g, i, m)
- `evaluateIsValid()` - Performs server-side validation
- `getMatchPattern(string)` - The PCRE pattern `/\A(?:expr)\z/` plus `PatternModifiers`; each `/` that is not escaped is escaped (@since 4.4.0)

## Patterns & Gotchas

- **Whole-value match** — The expression matches the whole value on the server and on the client. It is grouped between the anchors, so `\d{18}|\d{15}` rejects `123456789012345678xyz`, and `a|ab` accepts `ab`. The server anchors with `\A` and `\z`, so a trailing newline fails, as on the client.
- **Slashes** — Write `/` plain or escaped (`\/`); both work on both sides. Before 4.4.0, the server escaped `\/` again, so the pattern failed to compile.

## See Also

- [TBaseValidator](./TBaseValidator.md)
- [TRequiredFieldValidator](./TRequiredFieldValidator.md)
