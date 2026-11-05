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
- `getPauseWhenHidden()` / `setPauseWhenHidden($value)` - Whether the timer pauses while the page is hidden; default true. Only `false` is sent to the client (4.4.0)
- `getClientClassName()` - Returns `Prado.WebUI.TTimeTriggeredCallback`

## Pause and Resume (4.4.0)

The client timer keeps two states: `active` (between `startTimer()` and `stopTimer()`) and `timer` (the interval handle, set only while active and not paused).

| Condition | Result |
|---|---|
| `document.visibilityState === 'hidden'` and `PauseWhenHidden` | The interval is cleared; the timer stays active |
| `navigator.onLine === false` (the `offline` event) | The interval is cleared; a tick that still fires sends nothing |
| Page visible and online again (`visibilitychange`, `online`) | One callback at once when an interval passed since the last one (`lastTick`), then the interval is set again |
| `startTimer()` while hidden | Waits; the first callback is sent when the page is visible |
| `resetTimer()` / `setInterval()` on a stopped timer | Nothing; only an active timer restarts |

The server handler cannot assume one callback per interval: a hidden page sends none, and a page that becomes visible sends one early. `PauseWhenHidden=false` keeps the interval while hidden, at the rate the browser throttles a hidden page to (about once a minute in Chrome).

## See Also

- [TTriggeredCallback](./TTriggeredCallback.md), [TValueTriggeredCallback](./TValueTriggeredCallback.md), [TEventTriggeredCallback](./TEventTriggeredCallback.md)
