# Web/UI/ActiveControls/TActiveCheckBoxList

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TActiveCheckBoxList`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TActiveCheckBoxList.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Active counterpart to TCheckBoxList with AutoPostBack enabled. When a checkbox is clicked, raises OnSelectedIndexChanged followed by OnCallback. Selection changes are updated on the client side when EnableUpdate is true.

## Key Properties/Methods

- `createRepeatedControl()` - Creates [TActiveCheckBoxListItem](./TActiveCheckBoxListItem.md) for each item
- `raiseCallbackEvent($param)` - Raises callback event
- `onCallback($param)` - Event raised when callback is requested
- `getSpanNeeded()` - True for the Raw layout or an empty list; an empty list renders a span with the id, so a callback can replace it
- `getClientClassName()` - Returns `Prado.WebUI.TActiveCheckBoxList`

## See Also

- `TCheckBoxList`, [TActiveCheckBoxListItem](./TActiveCheckBoxListItem.md), [ICallbackEventHandler](./ICallbackEventHandler.md)
