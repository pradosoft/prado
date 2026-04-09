# TActiveCheckBox

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [ActiveControls](./INDEX.md) > [TActiveCheckBox](./TActiveCheckBox.md)

**Location:** `framework/Web/UI/ActiveControls/TActiveCheckBox.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview

Active counterpart to TCheckBox with AutoPostBack enabled by default. Text and Checked properties can be updated during callback and reflected on the client side.

## Key Properties/Methods

- `setText($value)` - Sets text with client-side update
- `setChecked($value)` - Sets checked state with client-side update
- `raiseCallbackEvent($param)` - Raises callback event
- `onCallback($param)` - Event raised when callback is requested
- `getClientClassName()` - Returns `Prado.WebUI.TActiveCheckBox`

## See Also

- `TCheckBox`, [ICallbackEventHandler](./ICallbackEventHandler.md), [TActiveCheckBoxList](./TActiveCheckBoxList.md)
