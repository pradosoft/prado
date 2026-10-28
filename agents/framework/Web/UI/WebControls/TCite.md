# Web/UI/WebControls/TCite

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TCite`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TCite.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
TCite represents the HTML `<cite>` element: the title of a creative work, such as a book, a paper, a song, a film, or a web page. It is not for the name of a person. A [TQuote](./TQuote.md) or [TBlockQuote](./TBlockQuote.md) gives the address of its source in `Cite`.

The default tag is `cite`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
As described in <com:TCite>The Pragmatic Programmer</com:TCite>.
```

## Accessibility

- `<cite>` has no ARIA role; browsers render it in italics and screen readers read it as ordinary text.

## Tests

- `tests/unit/Web/UI/WebControls/TCiteTest.php`
- `tests/playwright/web/TTextTagsTestCase.spec.js` with `tests/harness/web/protected/pages/TextTagsTest.page`: element, role (Chromium accessibility tree), attributes, and browser styles

## See Also

- [THtmlElement](./THtmlElement.md)
- [TQuote](./TQuote.md)
- [TBlockQuote](./TBlockQuote.md)

**@since 4.4.0**
