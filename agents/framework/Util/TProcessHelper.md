# Util/TProcessHelper

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TProcessHelper`**

## Class Info
**Location:** `framework/Util/TProcessHelper.php`
**Namespace:** `Prado\Util`

## Overview
Static utility class for process management: fork, kill, signal, priority, and OS detection. All methods are static. Requires `pcntl` and/or `posix` PHP extensions for full functionality; methods degrade gracefully when extensions are unavailable.

## Constants

```php
TProcessHelper::PHP_COMMAND           = '@php'   // placeholder replaced with PHP_BINARY in commands
TProcessHelper::FX_PREPARE_FOR_FORK   = 'fxPrepareForFork'   // global event before fork
TProcessHelper::FX_RESTORE_AFTER_FORK = 'fxRestoreAfterFork' // global event after fork

// Process priority constants (cross-platform, maps to OS values):
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
TProcessHelper::isForkable(): bool    // function_exists('pcntl_fork')
```

## Forking

```php
$pid = TProcessHelper::fork(bool $captureForkLog = false): int
// Returns: >0 in parent (child PID), 0 in child, -1 on failure
```

Before forking, fires global `fxPrepareForFork`. After forking (in both parent and child), fires `fxRestoreAfterFork`.

**`fxPrepareForFork` pattern:**
```php
public function fxPrepareForFork($sender, $param): ?array
{
    // Save state, close DB connections, etc.
    // Return data to receive back in fxRestoreAfterFork, or null
    return ['connection' => $this->_connection];
}

public function fxRestoreAfterFork($sender, $param): void
{
    $data = $param->getData();  // data from fxPrepareForFork return value
    // Reconnect, restore state for this process
}
```

When `$captureForkLog = true`, the `[TCaptureForkLog](../Behaviors/TCaptureForkLog.md)` behavior is automatically attached to collect child process logs.

## Signal & Kill

```php
TProcessHelper::sendSignal(int $signal, ?int $pid = null): bool
// Send signal to $pid (or current process if null). Uses posix_kill().

TProcessHelper::kill(int $pid): bool
// Send SIGKILL to $pid.

TProcessHelper::isRunning(int $pid): bool
// Check if process is alive (signal 0 probe).
```

## Process Priority

```php
TProcessHelper::getProcessPriority(?int $pid = null): ?int
// Returns nice value (null if unavailable). $pid=null = current process.

TProcessHelper::setProcessPriority(int $priority, ?int $pid = null): bool
// Set nice value. Returns false if permission denied or unavailable.
```

## Command Utilities

```php
TProcessHelper::filterCommand(string $command): string
// Replace '@php' placeholder with PHP_BINARY path.

TProcessHelper::escapeShellArg(string $argument): string
// Cross-platform shell argument escaping (handles Windows edge cases).

TProcessHelper::exitStatus(int $exitCode): int
// Extract actual exit status from pcntl_waitpid() status code.

TProcessHelper::isSurroundedBy(string $string, string $prefix): bool
// Check if string starts and ends with $prefix (e.g., quotes).
```

## Usage

```php
// Fork a worker process:
$pid = TProcessHelper::fork(captureForkLog: true);
if ($pid === 0) {
    // child process
    // do work...
    exit(0);
}
// parent: $pid is child's PID
[TSignalsDispatcher](../TSignalsDispatcher.md)::singleton()->attachPidHandler($pid, function($sender, $param) {
    // child exited
});

// Run a CLI command with PHP:
$cmd = TProcessHelper::filterCommand('@php prado-cli.php /app cron');
exec($cmd);

// Check if another process is still alive:
if (!TProcessHelper::isRunning($workerPid)) {
    // restart worker
}
```

## Patterns & Gotchas

- **`@php` placeholder** — use `TProcessHelper::PHP_COMMAND` in command strings; `filterCommand()` replaces it with the actual `PHP_BINARY` path, ensuring the correct PHP version is used.
- **`fxPrepareForFork` / `fxRestoreAfterFork`** — any `[TComponent](../TComponent.md)` that listens to these global events can clean up (e.g., close DB connections) before fork and re-initialize after. Always close database connections before fork to avoid shared socket issues.
- **`isForkable()`** — always check before calling `fork()` in portable code. Returns false on Windows.
- **Priority range** — `setProcessPriority()` uses Unix nice values: -20 (highest) to +19 (lowest). Requires root for negative values.
- **`[TCaptureForkLog](../Behaviors/TCaptureForkLog.md)`** — a behavior that routes child-process log entries back to the parent. Pass `captureForkLog: true` to `fork()` to auto-attach it.
