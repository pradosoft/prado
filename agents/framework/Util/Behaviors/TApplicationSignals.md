# TApplicationSignals

### Directories

[Util](../) > [Behaviors](Behaviors/) > TApplicationSignals

**Location:** `framework/Util/Behaviors/TApplicationSignals.php`
**Namespace:** `Prado\Util\Behaviors`

## Overview

This behavior installs the [TSignalsDispatcher](../TSignalsDispatcher.md) for the application when PHP pcntl extension is available. It connects POSIX signals (SIGTERM, SIGINT, SIGHUP) to application lifecycle events. The signals dispatcher class can be customized via `setSignalsClass()`.

## Key Properties/Methods

- `attachEventHandlers([TComponent](../TComponent.md) $component)` - Installs the signals dispatcher singleton
- `detachEventHandlers([TComponent](../TComponent.md) $component)` - Detaches the signals dispatcher
- `getSignalsDispatcher()` - Gets the [TSignalsDispatcher](../TSignalsDispatcher.md) instance
- `getSignalsClass()` / `setSignalsClass($value)` - Gets/sets the signals dispatcher class (default: TSignalsDispatcher)
- `getAsyncSignals()` / `setAsyncSignals($value)` - Gets/sets whether signals are handled asynchronously
- `getPriorHandlerPriority()` / `setPriorHandlerPriority($value)` - Gets/sets priority of original signal handlers

## See Also

- [TSignalsDispatcher](../TSignalsDispatcher.md)
- [TCaptureForkLog](./TCaptureForkLog.md)
