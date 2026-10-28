# Web/UI/WebControls/TSup

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TSup`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TSup.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
TSup represents the HTML `<sup>` element: a superscript required by a typographical convention, such as an exponent or an ordinal indicator. It is not for styling alone.

The default tag is `sup`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
E = mc<com:TSup>2</com:TSup>
```

## Accessibility

- Browsers expose `<sup>` with the `superscript` role.
- Most screen readers do not announce a superscript, so x<sup>2</sup> reads as "x 2"; text that depends on it says so in words or in a visually hidden span.
- A footnote reference is a link inside the TSup whose text names the footnote.

## Tests

- `tests/unit/Web/UI/WebControls/TSupTest.php`
- `tests/playwright/web/TTextTagsTestCase.spec.js` with `tests/harness/web/protected/pages/TextTagsTest.page`: element, role (Chromium accessibility tree), attributes, and browser styles

## See Also

- [THtmlElement](./THtmlElement.md)
- [TSub](./TSub.md)

**@since 4.4.0**
