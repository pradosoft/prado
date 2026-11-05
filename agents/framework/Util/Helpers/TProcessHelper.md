# Util/Helpers/TProcessHelper

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Helpers](./INDEX.md) / **`TProcessHelper`**

## Class Info
**Location:** `framework/Util/Helpers/TProcessHelper.php`
**Namespace:** `Prado\Util\Helpers`
**Since:** 4.3.0

## Overview
`TProcessHelper` is an all-static utility class for process-level operations: forking, signal sending, process priority, command construction, and OS detection. All methods are static; do not instantiate this class. Requires the `pcntl` and/or `posix` PHP extensions for full functionality; methods degrade gracefully when extensions are unavailable.

## Constants

```php
TProcessHelper::PHP_COMMAND           = '@php'              // replaced with PHP_BINARY in filterCommand()
TProcessHelper::FX_PREPARE_FOR_FORK   = 'fxPrepareForFork'  // global event before fork
TProcessHelper::FX_RESTORE_AFTER_FORK = 'fxRestoreAfterFork' // global event after fork

// Windows process priority (maps to Linux nice scale):
TProcessHelper::WINDOWS_IDLE_PRIORITY         = 19
TProcessHelper::WINDOWS_BELOW_NORMAL_PRIORITY = 8
TProcessHelper::WINDOWS_NORMAL_PRIORITY       = 0
TProcessHelper::WINDOWS_ABOVE_NORMAL_PRIORITY = -5
TProcessHelper::WINDOWS_HIGH_PRIORITY         = -10
TProcessHelper::WINDOWS_REALTIME_PRIORITY     = -17
```

## OS Detection

```php
TProcessHelper::isSystemWindows(): bool
TProcessHelper::isSystemMacOS(): bool
TProcessHelper::isSystemLinux(): bool
TProcessHelper::isForkable(): bool    // false on Windows or when pcntl unavailable
```

## Forking

```php
$pid = TProcessHelper::fork(bool $captureForkLog = false): int
// Returns: >0 in parent (child PID), 0 in child, -1 on failure
```

Before forking, fires global `fxPrepareForFork`. After forking (both parent and child), fires `fxRestoreAfterFork`. When `$captureForkLog = true`, attaches `[TCaptureForkLog](../Behaviors/TCaptureForkLog.md)` to route child-process logs back to the parent.

**Fork lifecycle pattern:**

```php
public function fxPrepareForFork($sender, $param): void
{
    $this->_db->close();   // close shared resources before fork
}

public function fxRestoreAfterFork($sender, $param): void
{
    $this->_db->open();    // reconnect in parent; child reconnects lazily
}
```

Always check `isForkable()` before calling `fork()` in portable code.

## Signals

```php
TProcessHelper::sendSignal(int $signal, ?int $pid = null): bool
// Sends $signal to $pid (or current process if null). Uses posix_kill().

TProcessHelper::kill(int $pid): bool
// Sends SIGKILL to $pid.

TProcessHelper::isRunning(int $pid): bool
// Returns true if the process is still alive.
```

## Process Priority

Linux nice range: `-20` (realtime) to `+19` (idle). Negative values require root.

```php
TProcessHelper::getProcessPriority(?int $pid = null): ?int
TProcessHelper::setProcessPriority(int $priority, ?int $pid = null): bool
```

## Command Construction

```php
TProcessHelper::filterCommand(string $command): string
// Replaces '@php' with PHP_BINARY; wraps with quotes on Windows.

TProcessHelper::escapeShellArg(string $argument): string
// Cross-platform shell argument escaping.

TProcessHelper::exitStatus(int $status): int
// Extracts actual exit code from pcntl_waitpid() status.
```

## Usage

```php
// Fork a worker process:
$pid = TProcessHelper::fork(captureForkLog: true);
if ($pid === 0) {
    // child process — do work
    exit(0);
}
// parent: register per-PID handler
[TSignalsDispatcher](../TSignalsDispatcher.md)::singleton()->attachPidHandler($pid, function($sender, $param) {
    // child exited
});

// Run a CLI command with the correct PHP binary:
$cmd = TProcessHelper::filterCommand('@php prado-cli.php /app cron');
exec($cmd);

// Check if a worker is still alive:
if (!TProcessHelper::isRunning($workerPid)) {
    // restart worker
}
```

## Patterns & Gotchas

- **`@php` placeholder** — use `TProcessHelper::PHP_COMMAND` in command strings; `filterCommand()` replaces it with `PHP_BINARY`, ensuring the right PHP version.
- **`fxPrepareForFork` / `fxRestoreAfterFork`** — always close DB connections before fork to avoid shared socket issues. Any `[TComponent](../../TComponent.md)` can listen to these global events.
- **`isForkable()`** — always check before `fork()` in portable code. Returns `false` on Windows.
- **`[TCaptureForkLog](../Behaviors/TCaptureForkLog.md)`** — routes child-process log entries back to the parent. Pass `captureForkLog: true` to `fork()` to auto-attach it.

## See Also

- [`TSignalsDispatcher`](../TSignalsDispatcher.md) — POSIX signal event dispatcher
- [`Helpers/INDEX.md`](./INDEX.md)
