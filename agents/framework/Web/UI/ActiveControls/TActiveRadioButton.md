# TActiveRadioButton

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [ActiveControls](./INDEX.md) > [TActiveRadioButton](./TActiveRadioButton.md)

**Location:** `framework/Web/UI/ActiveControls/TActiveRadioButton.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview

Active counterpart to TRadioButton with AutoPostBack enabled by default. Text and Checked properties can be updated during callback. GroupName cannot be changed during callback as the client-side name attribute is read-only.

## Key Properties/Methods

- `setText($value)` - Sets text with client-side update
- `setChecked($value)` - Sets checked state with client-side update
- `raiseCallbackEvent($param)` - Raises callback event
- `onCallback($param)` - Event raised when callback is requested
- `getClientClassName()` - Returns `Prado.WebUI.TActiveRadioButton`

## See Also

- `TRadioButton`, [ICallbackEventHandler](./ICallbackEventHandler.md), [TActiveRadioButtonList](./TActiveRadioButtonList.md)
