# TActiveButton

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [ActiveControls](./INDEX.md) > [TActiveButton](./TActiveButton.md)

**Location:** `framework/Web/UI/ActiveControls/TActiveButton.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview

Active counterpart to TButton. Clicking triggers a callback instead of postback. OnCallback is raised after OnClick. Text property can be updated during callback.

## Key Properties/Methods

- `setText($value)` - Sets button text with client-side update
- `raiseCallbackEvent($param)` - Raises OnClick then OnCallback
- `onCallback($param)` - Event raised when callback is requested
- `getClientClassName()` - Returns `Prado.WebUI.TActiveButton`

## See Also

- `TButton`, [ICallbackEventHandler](./ICallbackEventHandler.md)
