# TEventTriggeredCallback

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [ActiveControls](./INDEX.md) > [TEventTriggeredCallback](./TEventTriggeredCallback.md)

**Location:** `framework/Web/UI/ActiveControls/TEventTriggeredCallback.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview

Triggers callback requests when a specific DOM event occurs on a control. For example, can fire a callback when a user types in a textbox or moves focus. Optionally prevents the default event action.

## Key Properties/Methods

- `getEventName()` / `setEventName($value)` - Client-side event name to listen for
- `getPreventDefaultAction()` / `setPreventDefaultAction($value)` - Stop default event behavior
- `getClientClassName()` - Returns `Prado.WebUI.TEventTriggeredCallback`

## See Also

- [TTriggeredCallback](./TTriggeredCallback.md), [TTimeTriggeredCallback](./TTimeTriggeredCallback.md), [TValueTriggeredCallback](./TValueTriggeredCallback.md)
