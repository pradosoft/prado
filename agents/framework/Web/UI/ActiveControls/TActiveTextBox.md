# TActiveTextBox

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [ActiveControls](./INDEX.md) > [TActiveTextBox](./TActiveTextBox.md)

**Location:** `framework/Web/UI/ActiveControls/TActiveTextBox.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview

Active control version of TTextBox that triggers callback on text changes when AutoPostBack is true. Text property can be updated during callback and the change is reflected on the client without full page reload.

## Key Properties/Methods

- `setText($value)` - Sets text with client-side update support
- `raiseCallbackEvent($param)` - Raises callback event
- `onCallback($param)` - Event raised when callback is requested
- `getClientClassName()` - Returns `Prado.WebUI.TActiveTextBox`

## See Also

- `TTextBox`, [ICallbackEventHandler](./ICallbackEventHandler.md), [TInPlaceTextBox](./TInPlaceTextBox.md)
