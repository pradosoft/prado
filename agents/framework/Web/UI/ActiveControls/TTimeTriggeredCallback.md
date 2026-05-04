# Web/UI/ActiveControls/TTimeTriggeredCallback

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TTimeTriggeredCallback`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TTimeTriggeredCallback.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Triggers callback requests at fixed time intervals. Useful for polling scenarios where server needs to check for changes or update data periodically. Timer can be started/stopped programmatically or automatically on page load.

## Key Properties/Methods

- `getInterval()` / `setInterval($value)` - Seconds between callbacks (must be positive)
- `startTimer()` - Starts the timer
- `stopTimer()` - Stops the timer
- `getStartTimerOnLoad()` / `setStartTimerOnLoad($value)` - Auto-start on page load
- `getClientClassName()` - Returns `Prado.WebUI.TTimeTriggeredCallback`

## See Also

- [TTriggeredCallback](./TTriggeredCallback.md), [TValueTriggeredCallback](./TValueTriggeredCallback.md), [TEventTriggeredCallback](./TEventTriggeredCallback.md)
