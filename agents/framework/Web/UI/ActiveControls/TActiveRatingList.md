# Web/UI/ActiveControls/TActiveRatingList

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TActiveRatingList`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TActiveRatingList.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Clickable star rating control that behaves like a TRadioButtonList. Supports read-only mode, rating value changes during callback, and caption updates.

## Key Properties/Methods

- `setReadOnly($value)` - Set read-only mode with client-side update
- `setRating($value)` - Set rating value with client-side update
- `setCaption($value)` - Set caption with client-side update
- `raiseCallbackEvent($param)` - Raises callback event
- `onCallback($param)` - Event raised when callback is requested
- `getClientClassName()` - Returns `Prado.WebUI.TActiveRatingList`

## See Also

- `TRatingList`, [ICallbackEventHandler](./ICallbackEventHandler.md)
