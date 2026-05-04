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

## See Also

- [TBaseValidator](./TBaseValidator.md)
- [TRequiredFieldValidator](./TRequiredFieldValidator.md)
