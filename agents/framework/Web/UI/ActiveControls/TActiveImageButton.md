# Web/UI/ActiveControls/TActiveImageButton

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TActiveImageButton`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TActiveImageButton.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Active counterpart to TImageButton. Clicking triggers a callback instead of postback. OnCallback is raised after OnClick. Properties like AlternateText, ImageUrl can be updated during callback.

## Key Properties/Methods

- `setAlternateText($value)` - Sets alt text with client-side update
- `setImageUrl($value)` - Sets image URL with client-side update
- `raiseCallbackEvent($param)` - Raises OnClick then OnCallback
- `onCallback($param)` - Event raised when callback is requested
- `getClientClassName()` - Returns `Prado.WebUI.TActiveImageButton`

## See Also

- `TImageButton`, [ICallbackEventHandler](./ICallbackEventHandler.md)
