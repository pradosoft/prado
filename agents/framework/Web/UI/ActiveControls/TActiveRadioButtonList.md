# Web/UI/ActiveControls/TActiveRadioButtonList

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TActiveRadioButtonList`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TActiveRadioButtonList.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Active counterpart to TRadioButtonList. AutoPostBack is true by default, so selection changes trigger callbacks. Supports dynamic selection updates on the client side when EnableUpdate is true.

## Key Properties/Methods

- `createRepeatedControl()` - Creates [TActiveRadioButtonItem](./TActiveRadioButtonItem.md) for each item
- `raiseCallbackEvent($param)` - Raises callback event
- `onCallback($param)` - Event raised when callback is requested
- `getClientClassName()` - Returns `Prado.WebUI.TActiveRadioButtonList`

## See Also

- `TRadioButtonList`, [TActiveRadioButtonItem](./TActiveRadioButtonItem.md), [ICallbackEventHandler](./ICallbackEventHandler.md)
