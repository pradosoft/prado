# Web/UI/WebControls/TDescriptionList

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TDescriptionList`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TDescriptionList.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
TDescriptionList represents the HTML `<dl>` element: a list of name-value groups, such as terms and definitions, metadata, or questions and answers. Each group is one or more [TDescriptionTerm](./TDescriptionTerm.md) followed by one or more [TDescriptionDetails](./TDescriptionDetails.md); a TDescriptionDetails with `Term` renders its own term. A `<div>` may wrap each group.

The default tag is `dl`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
<com:TDescriptionList>
	<com:TDescriptionDetails Term="Author">Brad Anderson</com:TDescriptionDetails>
	<com:TDescriptionTerm>Editor</com:TDescriptionTerm>
	<com:TDescriptionTerm>Reviewer</com:TDescriptionTerm>
	<com:TDescriptionDetails>Qiang Xue</com:TDescriptionDetails>
</com:TDescriptionList>
```

## Accessibility

- Terms have the `term` role and details the `definition` role; screen readers read each term before its details.
- It is not for indenting text or for dialogue.

## Tests

- `tests/unit/Web/UI/WebControls/TDescriptionListTest.php`
- `tests/playwright/web/TStructureTagsTestCase.spec.js` with `tests/harness/web/protected/pages/StructureTagsTest.page`: roles (Chromium accessibility tree), menu postback, canvas fallback focus, and canvas drawing on load, resize, callback, and at pixel ratio 2

## See Also

- [THtmlElement](./THtmlElement.md)
- [TDescriptionTerm](./TDescriptionTerm.md)
- [TDescriptionDetails](./TDescriptionDetails.md)

**@since 4.4.0**
