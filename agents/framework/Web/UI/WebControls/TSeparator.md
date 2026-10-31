# Web/UI/WebControls/TSeparator

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TSeparator`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TSeparator.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [`THtmlElement`](./THtmlElement.md)

## Overview
TSeparator represents the HTML `<hr>` element: a thematic break between paragraph-level content. The element is void; child controls do not render.

## Template Usage

```xml
<com:TSeparator CssClass="chapter-break" />
```

## Accessibility

| Condition | Result |
|---|---|
| TagName `hr` | No `role`; the browser exposes a `separator`. |
| Another TagName (case-insensitive compare) | `role="separator"`, unless `Role` is set. |
| Decoration only | Use CSS, or `Role="presentation"`. |

## Tests

- `tests/unit/Web/UI/WebControls/TSeparatorTest.php`
- `tests/playwright/web/THtmlGroupingTestCase.spec.js` with `tests/harness/web/protected/pages/HtmlGroupingTest.page`

## See Also

- [THtmlElement](./THtmlElement.md)

**@since 4.4.0**
