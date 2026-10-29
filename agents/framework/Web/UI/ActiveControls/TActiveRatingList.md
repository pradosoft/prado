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

## Client Updates
During a callback, `setReadOnly()`, `setRating()`, and `setCaption()` call `callClientFunction()`, which sends `Prado.Registry['<ClientID>'].<method>(<value>)` with the value encoded by `TJavaScript::encode()`. The client writes the caption through `innerHTML`, as `TLabel` renders its text unencoded.

## Client Events
The client wrapper attaches its star and radio listeners through `observe()`, so a callback that keeps the elements (a morph) and registers a new wrapper removes the old listeners. The radio `change` selects the rating and sends the request; a click on the radio or its label checks the radio, and a click elsewhere in the star cell clicks the radio. One click sends one request, and a click on the current rating sends none (4.4.0).

## See Also

- `TRatingList`, [ICallbackEventHandler](./ICallbackEventHandler.md)
