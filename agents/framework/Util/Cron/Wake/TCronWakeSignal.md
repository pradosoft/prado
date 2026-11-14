# Util/Cron/TCronWakeSignal

### Directories
[framework](../../../INDEX.md) / [Util](../../INDEX.md) / [Cron](../INDEX.md) / [Wake](./INDEX.md) / **`TCronWakeSignal`**

## Class Info
**Location:** `framework/Util/Cron/Wake/TCronWakeSignal.php`
**Namespace:** `Prado\Util\Cron\Wake`
**Extends:** `TModule`
**Implements:** [`ICronWakeTransport`](ICronWakeTransport.md)
**Since:** 4.4.0

## Overview
`TCronWakeSignal` wakes the cron process with a process signal, `SIGUSR1` by default. The waiting process records its pid in `PidFile`; `signal()` reads the pid, checks the process is alive (`posix_kill($pid, 0)`), and sends `Signal`. It needs the `pcntl` and `posix` extensions and reaches the processes of one host.

| Side | Mechanism |
|---|---|
| per-minute `wait()` | installs a `pcntl` handler that records the signal, sleeps in `WAIT_SLICE` (50 ms) slices through `TAsync::sleep()`, and calls `pcntl_signal_dispatch()` between slices; a signal that arrived while the process was busy is consumed by the next wait |
| daemon `watch()` | the loop's `onSignal()`; the `wait()` handler is removed first so Revolt owns the signal |
| `close()` | cancels the watcher, sets the signal to `SIG_IGN`, and removes the pid file when it holds this pid |

## Properties

| Property | Default | Description |
|---|---|---|
| `PidFile` | `<runtime>/cronwake.pid` | Where the waiting process records its pid; a path or namespace. |
| `Signal` | `SIGUSR1` | The signal number. |

## See Also

- [ICronWakeTransport](ICronWakeTransport.md)
- [TCronModule](../TCronModule.md)
