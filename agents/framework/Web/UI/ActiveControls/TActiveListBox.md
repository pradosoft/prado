# TActiveListBox

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [ActiveControls](./INDEX.md) > [TActiveListBox](./TActiveListBox.md)

**Location:** `framework/Web/UI/ActiveControls/TActiveListBox.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview

Active counterpart to TListBox with AutoPostBack enabled. List items can be added dynamically during callback response. Selection mode can be changed during callback.

## Key Properties/Methods

- `createListItemCollection()` - Creates [TActiveListItemCollection](./TActiveListItemCollection.md)
- `setSelectionMode($value)` - Sets selection mode with client-side update
- `raiseCallbackEvent($param)` - Raises callback event
- `onCallback($param)` - Event raised when callback is requested
- `onPreRender($param)` - Updates client-side list items if changed
- `getClientClassName()` - Returns `Prado.WebUI.TActiveListBox`

## See Also

- `TListBox`, [TActiveListItemCollection](./TActiveListItemCollection.md), [ICallbackEventHandler](./ICallbackEventHandler.md)
