# Web/UI/WebControls/TRubyText

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TRubyText`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TRubyText.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
TRubyText represents the HTML `<rt>` element: the ruby annotation of the base text before it in a [TRuby](./TRuby.md), such as a reading or a translation.

The default tag is `rt`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Accessibility

- Screen readers read the annotation after its base text.

## Tests

- `tests/unit/Web/UI/WebControls/TRubyTest.php`
- `tests/playwright/web/TRubyTestCase.spec.js` with `tests/harness/web/protected/pages/RubyTest.page`: ruby layout, hidden parentheses, reading order, and the Chromium accessibility tree

## See Also

- [TRuby](./TRuby.md)

**@since 4.4.0**
