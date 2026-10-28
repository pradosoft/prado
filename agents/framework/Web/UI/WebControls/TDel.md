# Web/UI/WebControls/TDel

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TDel`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TDel.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `TBaseEdit`

## Overview
TDel represents the HTML `<del>` element: text removed from the document. [TBaseEdit](./TBaseEdit.md) holds `Cite` and `DateTime`.

The default tag is `del`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
<com:TDel DateTime="2026-10-01">$40</com:TDel> <com:TIns DateTime="2026-10-01">$30</com:TIns>
```

## Key Properties

| Property | Description |
|---|---|
| `Cite` | URL of the explanation of the edit |
| `DateTime` | When the edit was made; see [TBaseEdit](./TBaseEdit.md) |

## Accessibility

- Browsers expose `<del>` with the `deletion` role; see [TBaseEdit](./TBaseEdit.md).

## Tests

- `tests/unit/Web/UI/WebControls/TDelTest.php`
- `tests/playwright/web/TTextTagsTestCase.spec.js` with `tests/harness/web/protected/pages/TextTagsTest.page`: element, role (Chromium accessibility tree), attributes, and browser styles

## See Also

- [THtmlElement](./THtmlElement.md)
- [TBaseEdit](./TBaseEdit.md)
- [TIns](./TIns.md)

**@since 4.4.0**
