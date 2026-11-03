# Web/UI/ActiveControls/TServerEventClientSide

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TServerEventClientSide`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TServerEventClientSide.php`
**Namespace:** `Prado\Web\UI\ActiveControls`
**Extends:** [`TCallbackClientSide`](TCallbackClientSide.md)
**Since:** 4.4.0

## Overview
The client-side options of [`TServerEventTriggeredCallback`](TServerEventTriggeredCallback.md), set in its constructor. It adds one event to the callback options.

| Property | Option | Notes |
|---|---|---|
| `OnServerEvent` | `OnServerEvent` | javascript run for each server event that matches `EventName`, before its callback, as `function(sender, parameter){ … }`; `parameter` is `{Channel, ID, Event, Data}`. `return false;` skips that callback. |

```xml
<com:TServerEventTriggeredCallback Channel="orders" OnCallback="ordersChanged"
    ClientSide.OnServerEvent="if (parameter.Data.silent) { return false; }" />
```

With `CallbackOnEvent="false"` on the control, `OnServerEvent` is the only thing that runs.
