# Web/UI/WebControls/TMenuList

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TMenuList`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TMenuList.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `TBulletedList`

## Overview
TMenuList renders the items of a [TBulletedList](./TBulletedList.md) in the HTML `<menu>` element, a toolbar of commands. Items post back with `DisplayMode="LinkButton"` and raise `OnClick`. A `<menu>` is an unordered list, so a numbered `BulletStyle` changes only the markers.

The default tag is `menu`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
<com:TMenuList DisplayMode="LinkButton" OnClick="menuClicked" Aria.label="Edit">
	<com:TListItem Text="Cut" Value="cut" />
	<com:TListItem Text="Copy" Value="copy" />
</com:TMenuList>
```

## Accessibility

- Browsers expose `<menu>` with the `list` role; screen readers announce the item count.
- It is not an ARIA `menu`: `role="menu"` requires arrow-key navigation that TMenuList does not provide.
- More than one toolbar on a page → name each with `Aria.label`.
- `LinkButton` items are links, and screen readers announce them as links.

## Tests

- `tests/unit/Web/UI/WebControls/TMenuListTest.php`
- `tests/playwright/web/TStructureTagsTestCase.spec.js` with `tests/harness/web/protected/pages/StructureTagsTest.page`: roles (Chromium accessibility tree), menu postback, canvas fallback focus, and canvas drawing on load, resize, callback, and at pixel ratio 2

## See Also

- [THtmlElement](./THtmlElement.md)
- [TBulletedList](./TBulletedList.md)

**@since 4.4.0**
