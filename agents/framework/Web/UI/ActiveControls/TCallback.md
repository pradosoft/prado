# Web/UI/ActiveControls/TCallback

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TCallback`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TCallback.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Basic callback handler component. Invoked from client-side JavaScript to trigger server-side processing without full page postback. Raises `OnCallback` event when requested. Can be used for arbitrary server actions triggered by custom UI elements.

## Key Properties/Methods

- `getActiveControl()` - Returns [TBaseActiveCallbackControl](./TBaseActiveCallbackControl.md) options
- `getClientSide()` - Returns [TCallbackClientSide](./TCallbackClientSide.md) options
- `raiseCallbackEvent($param)` - Raises callback event with optional validation
- `onCallback($param)` - Event raised when callback is requested

## See Also

- [ICallbackEventHandler](./ICallbackEventHandler.md), [TBaseActiveCallbackControl](./TBaseActiveCallbackControl.md), [TCallbackClientScript](./TCallbackClientScript.md)
