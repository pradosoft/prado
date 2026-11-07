# Web/UI/WebControls/TCustomizableSelectTrait

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TCustomizableSelectTrait`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TCustomizableSelectTrait.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Since:** 4.4.0

## Overview
TCustomizableSelectTrait backs the `Customizable` property of a `<select>` list control. A customizable select renders the `customizable-select` class (`CUSTOMIZABLE_CSS_CLASS`) before `CssClass`, and the page registers one style block, keyed by the class, that opts it into `appearance: base-select`:

```css
select.customizable-select, select.customizable-select::picker(select) { appearance: base-select }
```

The picker shows the HTML `Content` of each [TListItem](./TListItem.md).

## Methods

| Method | Description |
|---|---|
| `getCustomizable()` / `setCustomizable($value)` | Viewstate `Customizable`; default `false` |
| `getCustomizableCssUrl()` / `setCustomizableCssUrl($value)` | Viewstate `CustomizableCssUrl`. `'default'` → the style block; a URL → `registerStyleSheetFile()`; `''` → nothing |
| `registerCustomizableStyleSheet()` | Protected. Registers the stylesheet of a customizable select; the using class calls it from `onPreRender()` |
| `addCustomizableAttributesToRender($writer)` | Protected. Replaces `class` with `customizable-select` and `CssClass`; the using class calls it after `parent::addAttributesToRender()` |

## Using Classes

| Class | Markup |
|---|---|
| [TDropDownList](./TDropDownList.md) | `<button><selectedcontent></selectedcontent></button>` before the options, so the button shows the selected option's content |
| [TListBox](./TListBox.md) | Options only; a list box has no button |

The active controls inherit it. A callback that replaces the options keeps the `<button>` (`Prado.Element.setOptions`).

## Fallback

A browser without customizable select support ignores `appearance: base-select`, the `<button>` and `<selectedcontent>` tags, and the elements inside `<option>`. It renders a native select with the text of each option.

## Accessibility

- The accessible name of an option is its text content. Option `Content` contains the item's text.
- Decorative images and icons in `Content` have `alt=""` or `aria-hidden="true"`, so the name is not repeated.
- The `<button>` holds only `<selectedcontent>`; other content in it changes the select's accessible value.
- `::picker-icon` and `::checkmark` are not in the accessibility tree.
- Styles for the picker keep a visible focus indicator and a 3:1 contrast on the selected option.
