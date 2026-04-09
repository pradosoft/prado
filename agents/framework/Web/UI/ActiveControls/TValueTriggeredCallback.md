# TValueTriggeredCallback

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [ActiveControls](./INDEX.md) > [TValueTriggeredCallback](./TValueTriggeredCallback.md)

**Location:** `framework/Web/UI/ActiveControls/TValueTriggeredCallback.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview

Triggers callback requests when a control property value changes. Polls the property every specified interval seconds and fires a callback when the value differs from the previous check. Supports decay rate to increase polling interval linearly when no changes are observed.

## Key Properties/Methods

- `getPropertyName()` / `setPropertyName($value)` - Property to observe
- `getInterval()` / `setInterval($value)` - Polling interval in seconds (default 1)
- `getDecayRate()` / `setDecayRate($value)` - Interval increase rate when no changes
- `getClientClassName()` - Returns `Prado.WebUI.TValueTriggeredCallback`

## See Also

- [TTriggeredCallback](./TTriggeredCallback.md), [TTimeTriggeredCallback](./TTimeTriggeredCallback.md), [TEventTriggeredCallback](./TEventTriggeredCallback.md)
