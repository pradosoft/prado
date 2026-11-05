# Web/UI/WebControls/TDescriptionTerm

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TDescriptionTerm`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TDescriptionTerm.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
TDescriptionTerm represents the HTML `<dt>` element: the name of a group in a [TDescriptionList](./TDescriptionList.md). A [TDescriptionDetails](./TDescriptionDetails.md) with `Term` renders a one-term group without it.

The default tag is `dt`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
<com:TDescriptionTerm>Editor</com:TDescriptionTerm>
```

## Accessibility

- Browsers expose `<dt>` with the `term` role.

## Tests

- `tests/unit/Web/UI/WebControls/TDescriptionTermTest.php`
- `tests/playwright/web/TStructureTagsTestCase.spec.js` with `tests/harness/web/protected/pages/StructureTagsTest.page`: roles (Chromium accessibility tree), menu postback, canvas fallback focus, and canvas drawing on load, resize, callback, and at pixel ratio 2

## See Also

- [THtmlElement](./THtmlElement.md)
- [TDescriptionList](./TDescriptionList.md)
- [TDescriptionDetails](./TDescriptionDetails.md)

**@since 4.4.0**
