# TSignalsDispatcher

### Directories

[Util](../) > TSignalsDispatcher

**Location:** `framework/Util/TSignalsDispatcher.php`
**Namespace:** `Prado\Util`

## Overview

Singleton POSIX signal dispatcher. Translates OS signals into PRADO global (`fx*`) events so application code can react to `SIGTERM`, `SIGINT`, `SIGCHLD`, `SIGALRM`, and others without using `pcntl_signal()` directly.

Implements `[ISingleton](../I18N/TGlobalization.md)`. Requires the PHP `pcntl` extension.

## Singleton

```php
$dispatcher = TSignalsDispatcher::singleton();           // create if not exists
$dispatcher = TSignalsDispatcher::singleton(false);      // return existing or null
TSignalsDispatcher::singleton()->attach();               // install signal handlers
TSignalsDispatcher::singleton()->detach();               // restore prior handlers
```

## Signal → Event Mapping

```php
TSignalsDispatcher::SIGNAL_MAP = [
    SIGALRM  => 'fxSignalAlarm',
    SIGHUP   => 'fxSignalHangUp',
    SIGINT   => 'fxSignalInterrupt',
    SIGQUIT  => 'fxSignalQuit',
    SIGTRAP  => 'fxSignalTrap',
    SIGABRT  => 'fxSignalAbort',
    SIGUSR1  => 'fxSignalUser1',
    SIGUSR2  => 'fxSignalUser2',
    SIGTERM  => 'fxSignalTerminate',
    SIGCHLD  => 'fxSignalChild',
    SIGCONT  => 'fxSignalContinue',
    SIGTSTP  => 'fxSignalTerminalStop',
    SIGTTIN  => 'fxSignalBackgroundRead',
    SIGTTOU  => 'fxSignalBackgroundWrite',
    SIGURG   => 'fxSignalUrgent',
]
```

## Named Constants

```php
TSignalsDispatcher::FX_SIGNAL_ALARM         = 'fxSignalAlarm'
TSignalsDispatcher::FX_SIGNAL_INTERRUPT     = 'fxSignalInterrupt'
TSignalsDispatcher::FX_SIGNAL_TERMINATE     = 'fxSignalTerminate'
TSignalsDispatcher::FX_SIGNAL_CHILD         = 'fxSignalChild'
// ...etc for all signals
TSignalsDispatcher::EXIT_SIGNALS            // [SIGTERM, SIGINT, SIGQUIT, ...]
TSignalsDispatcher::NULL_ALARM              = [self::class, 'nullAlarm']
```

## attach() / detach()

```php
$dispatcher->attach(): bool   // installs handlers; saves prior handlers; returns false if no pcntl
$dispatcher->detach(): bool   // restores prior handlers; unregisters
```

Prior handlers are saved and restored on `detach()` so third-party signal handling is not disrupted.

## SIGCHLD — Per-PID Handlers

SIGCHLD dispatches to the global `fxSignalChild` event AND to any registered per-PID handler:

```php
$dispatcher->attachPidHandler(int $pid, callable $handler, mixed $priority = null): void
$dispatcher->detachPidHandler(int $pid, callable $handler, mixed $priority = false): void
$dispatcher->hasPidHandler(int $pid): bool
$dispatcher->getPidHandlers(int $pid, bool $validate = false): array
$dispatcher->clearPidHandlers(int $pid): bool
$dispatcher->delegateChild($sender, $param): void  // internal SIGCHLD router
```

PID handlers are tracked via `WeakMap`. Handlers are called when the child PID signals.

## SIGALRM — Alarm / Timer

```php
TSignalsDispatcher::alarm(int $seconds, ?callable $callback = null): ?int
// Sets a one-shot alarm. Returns previous alarm time.
// If $callback is provided, it is registered as a one-time fxSignalAlarm handler.

TSignalsDispatcher::disarm(?int $alarmTime = null, ?callable $callback = null): ?int
// Cancels alarm or removes a specific callback.

TSignalsDispatcher::nullAlarm($sender, $param): void
// No-op alarm handler — cancels the alarm without side effects.
```

## Async vs Synchronous Dispatch

```php
TSignalsDispatcher::getAsyncSignals(): ?bool       // null if pcntl unavailable
TSignalsDispatcher::setAsyncSignals(bool $v): ?bool // pcntl_async_signals(true/false)
TSignalsDispatcher::syncDispatch(): ?bool           // pcntl_signal_dispatch()
```

## Static Helpers

```php
TSignalsDispatcher::hasSignals(): bool              // checks for pcntl_signal() availability
TSignalsDispatcher::getSignalFromEvent(string $evt): ?int  // reverse-map event name to signal
TSignalsDispatcher::getPriorHandlerPriority(): ?float      // priority for saved prior handlers
TSignalsDispatcher::setPriorHandlerPriority(?float $v): bool
TSignalsDispatcher::getPriorHandler(int $signal, bool $original = false): mixed
```

## Usage

```php
// Graceful shutdown on SIGTERM:
$dispatcher = TSignalsDispatcher::singleton();
$dispatcher->attach();

[Prado](../Prado.md)::getApplication()->attachEventHandler(
    TSignalsDispatcher::FX_SIGNAL_TERMINATE,
    function($sender, $param) {
        // flush queues, close DB connections, etc.
        exit(0);
    }
);

// Or via TApplicationSignals behavior (preferred — see TBehaviorsModule):
// <behavior class="Prado\Util\Behaviors\TApplicationSignals" ... />
```

## Patterns & Gotchas

- **Singleton** — only one instance per process. `attach()` on an already-attached dispatcher is a no-op; check return value.
- **`[TApplicationSignals](Behaviors/TApplicationSignals.md)` behavior** — the recommended way to use signals in a PRADO app. It attaches `TSignalsDispatcher` and maps `SIGTERM`/`SIGINT` to graceful shutdown.
- **SIGCHLD race** — when forking with `[TProcessHelper](Helpers/TProcessHelper.md)::fork()`, register PID handlers before `fork()` to avoid race conditions.
- **`pcntl` required** — `hasSignals()` returns `false` if the extension is not loaded. All signal methods return `null` gracefully in that case.
- **Prior handlers** — if a signal had a prior PHP handler before `attach()`, it is preserved in the `fxSignal*` event at `getPriorHandlerPriority()` (default: very low priority, so app handlers run first).
