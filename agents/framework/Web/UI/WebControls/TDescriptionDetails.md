# Web/UI/WebControls/TDescriptionDetails

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TDescriptionDetails`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TDescriptionDetails.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
TDescriptionDetails represents the HTML `<dd>` element: the value of a group in a [TDescriptionList](./TDescriptionList.md). When `Term` is set, a `<dt>` holding it renders before the `<dd>`, so one control renders a one-term group. The control's attributes render on the `<dd>`.

The default tag is `dd`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
<com:TDescriptionDetails Term="Author">Brad Anderson</com:TDescriptionDetails>
```

## Key Properties

| Property | Description |
|---|---|
| `Term` | Rendered in a `<dt>` before the `<dd>` when set |
| `Encode` | HTML-encodes `Term` ([TEncodeTrait](./TEncodeTrait.md)); default false |

## Accessibility

- Browsers expose `<dd>` with the `definition` role and the `<dt>` with the `term` role.

## Tests

- `tests/unit/Web/UI/WebControls/TDescriptionDetailsTest.php`
- `tests/playwright/web/TStructureTagsTestCase.spec.js` with `tests/harness/web/protected/pages/StructureTagsTest.page`: roles (Chromium accessibility tree), menu postback, canvas fallback focus, and canvas drawing on load, resize, callback, and at pixel ratio 2

## See Also

- [THtmlElement](./THtmlElement.md)
- [TDescriptionList](./TDescriptionList.md)
- [TDescriptionTerm](./TDescriptionTerm.md)

**@since 4.4.0**
