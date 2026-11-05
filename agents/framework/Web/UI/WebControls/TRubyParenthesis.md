# Web/UI/WebControls/TRubyParenthesis

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TRubyParenthesis`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TRubyParenthesis.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
TRubyParenthesis represents the HTML `<rp>` element: a parenthesis around a [TRubyText](./TRubyText.md). A browser that renders ruby hides it; otherwise the annotation shows in parentheses after the base text.

The default tag is `rp`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Accessibility

- A browser that renders ruby hides the parentheses from screen readers as well.

## Tests

- `tests/unit/Web/UI/WebControls/TRubyTest.php`
- `tests/playwright/web/TRubyTestCase.spec.js` with `tests/harness/web/protected/pages/RubyTest.page`: ruby layout, hidden parentheses, reading order, and the Chromium accessibility tree

## See Also

- [TRuby](./TRuby.md)

**@since 4.4.0**
