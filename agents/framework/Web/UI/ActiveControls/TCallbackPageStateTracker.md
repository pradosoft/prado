# Web/UI/ActiveControls/TCallbackPageStateTracker

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TCallbackPageStateTracker`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TCallbackPageStateTracker.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Tracks viewstate changes during callback to enable fine-grained client-side updates. Tracks properties like Visible, Enabled, Attributes, Style, TabIndex, ToolTip, AccessKey, and KeyShortcuts, then calls corresponding client-side update handlers when changes are detected.

## Key Properties/Methods

- `trackChanges()` - Captures current viewstate values
- `respondToChanges()` - Calls handlers for detected changes
- `getStatesToTrack()` - Returns TMap of states being tracked
- `updateVisible($visible)`, `updateEnabled($enable)` - Client update handlers
- `updateStyle($style)`, `updateAttributes($attributes)` - Client update handlers
- `updateKeyShortcuts()` - Sends `TWebControl::getKeyShortcutsAttributes()` once per callback when `KeyShortcuts`, `KeyShortcutScope`, or `KeyShortcutAllowReserved` changes, targeting the ClientID element (the input of a TCheckBox), and registers the `keyshortcuts` package (4.4.0)

## See Also

- [TViewStateDiff](./TViewStateDiff.md), [TActiveControlAdapter](./TActiveControlAdapter.md)
