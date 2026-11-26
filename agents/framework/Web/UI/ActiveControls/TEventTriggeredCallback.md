# Web/UI/ActiveControls/TEventTriggeredCallback

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TEventTriggeredCallback`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TEventTriggeredCallback.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Triggers callback requests when a specific DOM event occurs on a control. For example, can fire a callback when a user types in a textbox or moves focus. Optionally prevents the default event action.

## Key Properties/Methods

- `getEventName()` / `setEventName($value)` - Client-side event name to listen for. The client receives it in lower case without an `on` prefix in any case (`OnKeyUp` → `keyup`). Without an event name, no `EventName` option is sent and the client listens to `change` on a text input or a list, and to `click` otherwise
- `getPreventDefaultAction()` / `setPreventDefaultAction($value)` - Stop default event behavior
- `getClientClassName()` - Returns `Prado.WebUI.TEventTriggeredCallback`

## See Also

- [TTriggeredCallback](./TTriggeredCallback.md), [TTimeTriggeredCallback](./TTimeTriggeredCallback.md), [TValueTriggeredCallback](./TValueTriggeredCallback.md)
