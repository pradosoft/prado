# Web/UI/ActiveControls/TInPlaceTextBox

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TInPlaceTextBox`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TInPlaceTextBox.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Click-to-edit text box that displays as a label until clicked. When clicked, transforms into a text input for editing. Supports loading text from server via callback before allowing edit, and can auto-hide the textbox after losing focus.

## Key Properties/Methods

- `getAutoHideTextBox()` / `setAutoHideTextBox($value)` - Hide textbox after blur (default true)
- `getDisplayTextBox()` / `setDisplayTextBox($value)` - Show/hide edit textbox
- `getEditTriggerControlID()` / `setEditTriggerControlID($value)` - External trigger control ID
- `getReadOnly()` / `setReadOnly($value)` - Make control non-editable
- `onLoadingText($param)` - Event raised to load text from server before editing
- `getClientClassName()` - Returns `Prado.WebUI.TInPlaceTextBox`

## See Also

- [TActiveTextBox](./TActiveTextBox.md), [TCallback](./TCallback.md)
