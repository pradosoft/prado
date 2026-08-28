# Web/UI/ActiveControls/TInPlaceDropDownList

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TInPlaceDropDownList`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TInPlaceDropDownList.php`
**Namespace:** `Prado\Web\UI\ActiveControls`
**Since:** 4.4.0

## Overview
Click-to-edit drop down list that displays as a label showing the selected item text. Clicking the label swaps to the server-rendered (hidden) select element. The select's `change` event posts a callback that raises `OnSelectedIndexChanged` and `OnCallback`, updates the label, and re-hides the select when `AutoHideEditor` is true; losing focus leaves edit mode. The label carries the control's CssClass, style, and ToolTip. Shares the label/editor swap machinery with `TInPlaceTextBox` through `TInPlaceControlTrait` (PHP) and `Prado.WebUI.TInPlaceControlBase` (JS, `inlineeditor.js`), which owns the instance registry keyed by editor client ID.

## Key Properties/Methods

- `getAutoHideEditor()` / `setAutoHideEditor($value)` - Hide the select after blur (default true); from `TInPlaceControlTrait`
- `getDisplayEditor()` / `setDisplayEditor($value)` - Show/hide the select; from `TInPlaceControlTrait`
- `getEditTriggerControlID()` / `setEditTriggerControlID($value)` - External trigger control ID
- `getReadOnly()` / `setReadOnly($value)` - Prevent entering edit mode
- `getEmptyDisplayText()` / `setEmptyDisplayText($value)` - Label html when the selection has no text; the label carries a `data-prado-empty` mark so the client tells the placeholder apart from a value
- `onLoadingItems($param)` - Event raised to load the item list from the server on edit
- `setSelectedValue($value)` / `setSelectedIndex($index)` - Also update the client-side label during callbacks
- `getClientClassName()` - Returns `Prado.WebUI.TInPlaceDropDownList`

## See Also

- [TInPlaceTextBox](./TInPlaceTextBox.md), [TInPlaceListBox](./TInPlaceListBox.md), [TActiveDropDownList](./TActiveDropDownList.md)
