# Web/UI/ActiveControls/TInPlaceListBox

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TInPlaceListBox`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TInPlaceListBox.php`
**Namespace:** `Prado\Web\UI\ActiveControls`
**Since:** 4.4.0

## Overview
Click-to-edit list box that displays as a label showing the selected item texts. Clicking the label swaps to the server-rendered (hidden) select, which may allow multiple selection. In multiple mode the label joins the selected item texts with `SelectionSeparator`, and the selection accumulates across clicks and commits on blur (leaving the editor) — an individual toggle does not post, so a multi-item selection can be built interactively. A single-selection list box commits on change like the drop down list. The committing callback raises `OnSelectedIndexChanged` and `OnCallback`; the label follows the server's selection on any callback (the client-side snapshot for revert keys on option index, so duplicate option values are handled correctly). When nothing is selected the label shows `EmptyDisplayText` and carries a `data-prado-empty` mark. Shares the select-based in-place surface with `TInPlaceDropDownList` through `TInPlaceListControlTrait` (PHP) and `Prado.WebUI.TInPlaceDropDownList` (JS, its base class in `inlineeditor.js`).

## Key Properties/Methods

- `getSelectionSeparator()` / `setSelectionSeparator($value)` - Text between selected item texts in the label (default ", ")
- `getAutoHideEditor()` / `setAutoHideEditor($value)` - Hide the select after blur (default true); from `TInPlaceControlTrait`
- `getDisplayEditor()` / `setDisplayEditor($value)` - Show/hide the select; from `TInPlaceControlTrait`
- `getEditTriggerControlID()` / `setEditTriggerControlID($value)` - External trigger control ID
- `getReadOnly()` / `setReadOnly($value)` - Prevent entering edit mode
- `getEmptyDisplayText()` / `setEmptyDisplayText($value)` - Label html when nothing is selected
- `onLoadingItems($param)` - Event raised to load the item list from the server on edit
- `getClientClassName()` - Returns `Prado.WebUI.TInPlaceListBox`

## Accessibility

The label is rendered as an operable button (`role="button"`, `tabindex="0"`) with `aria-live="polite"`; Enter/Space enter edit mode, and focus returns to the label after an Enter/Escape/change commit. A read-only control renders the label as plain text (no button role). The editor takes its accessible name from `ToolTip` (sent as the `EditorLabel` option). Shared via `TInPlaceControlTrait::renderLabelAccessibilityAttributes` (PHP) and the base class in `inlineeditor.js` (JS).

## See Also

- [TInPlaceDropDownList](./TInPlaceDropDownList.md), [TActiveListBox](./TActiveListBox.md)
