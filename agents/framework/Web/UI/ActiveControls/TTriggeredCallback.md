# TTriggeredCallback

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [ActiveControls](./INDEX.md) > [TTriggeredCallback](./TTriggeredCallback.md)

**Location:** `framework/Web/UI/ActiveControls/TTriggeredCallback.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview

Abstract base class for triggered callback controls. Observes a server control and triggers callback requests when specific events or conditions occur on that control.

## Key Properties/Methods

- `getControlID()` / `setControlID($value)` - ID of the control to observe
- `getTargetControl()` - Gets the client ID of target control
- `getTriggerOptions()` - Returns array of trigger options for client-side

## See Also

- [TTimeTriggeredCallback](./TTimeTriggeredCallback.md), [TValueTriggeredCallback](./TValueTriggeredCallback.md), [TEventTriggeredCallback](./TEventTriggeredCallback.md)
