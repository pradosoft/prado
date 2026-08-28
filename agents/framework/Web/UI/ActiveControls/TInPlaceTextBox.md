# Web/UI/ActiveControls/TInPlaceTextBox

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TInPlaceTextBox`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TInPlaceTextBox.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Click-to-edit text box that displays as a label until clicked. When clicked, transforms into a text input for editing. Supports loading text from server via callback before allowing edit, and can auto-hide the textbox after losing focus. The client-side input honors the full `TTextBoxMode` set (Date, Number, Email, Color, etc.), matching `TTextBox` type rendering. Shares the label/editor swap machinery with `TInPlaceDropDownList` through `TInPlaceControlTrait` (PHP) and `Prado.WebUI.TInPlaceControlBase` (JS, `inlineeditor.js`).

## Key Properties/Methods

- `getAutoHideEditor()` / `setAutoHideEditor($value)` - Hide textbox after blur (default true); `AutoHideTextBox` is a deprecated alias
- `getDisplayEditor()` / `setDisplayEditor($value)` - Show/hide edit textbox; `DisplayTextBox` is a deprecated alias
- `getEditTriggerControlID()` / `setEditTriggerControlID($value)` - External trigger control ID
- `getReadOnly()` / `setReadOnly($value)` - Make control non-editable
- `getEmptyDisplayText()` / `setEmptyDisplayText($value)` - Label html when the text is empty (since 4.4.0, via `TInPlaceControlTrait`); the label carries a `data-prado-empty` mark so the client tells the placeholder apart from a value
- `onLoadingText($param)` - Event raised to load text from server before editing
- `getClientClassName()` - Returns `Prado.WebUI.TInPlaceTextBox`

## See Also

- [TActiveTextBox](./TActiveTextBox.md), [TCallback](./TCallback.md), [TInPlaceDropDownList](./TInPlaceDropDownList.md)
