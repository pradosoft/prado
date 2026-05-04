# Web/UI/ActiveControls/TActiveLinkButton

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TActiveLinkButton`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TActiveLinkButton.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Active counterpart to TLinkButton. Clicking triggers a callback instead of postback. Text property can be updated during callback. Enabled property changes are reflected on the client by updating the href attribute.

## Key Properties/Methods

- `setText($value)` - Sets text with client-side update
- `setEnabled($value)` - Sets enabled state with client-side update
- `raiseCallbackEvent($param)` - Raises OnClick then OnCallback
- `onCallback($param)` - Event raised when callback is requested
- `getClientClassName()` - Returns `Prado.WebUI.TActiveLinkButton`

## See Also

- `TLinkButton`, [ICallbackEventHandler](./ICallbackEventHandler.md)
