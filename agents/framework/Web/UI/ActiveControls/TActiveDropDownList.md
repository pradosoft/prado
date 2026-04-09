# TActiveDropDownList

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [ActiveControls](./INDEX.md) > [TActiveDropDownList](./TActiveDropDownList.md)

**Location:** `framework/Web/UI/ActiveControls/TActiveDropDownList.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview

Active counterpart to TDropDownList with AutoPostBack enabled. Selection changes trigger callbacks. List items can be added dynamically during callback. Uses [TActiveListControlAdapter](./TActiveListControlAdapter.md).

## Key Properties/Methods

- `createListItemCollection()` - Creates [TActiveListItemCollection](./TActiveListItemCollection.md)
- `raiseCallbackEvent($param)` - Raises callback event
- `onCallback($param)` - Event raised when callback is requested
- `onPreRender($param)` - Updates client-side list items if changed
- `getClientClassName()` - Returns `Prado.WebUI.TActiveDropDownList`

## See Also

- `TDropDownList`, [TActiveListItemCollection](./TActiveListItemCollection.md), [ICallbackEventHandler](./ICallbackEventHandler.md)
