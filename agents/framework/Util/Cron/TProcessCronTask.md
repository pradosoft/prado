# Util/Cron/TProcessCronTask

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TProcessCronTask`**

## Class Info
**Location:** `framework/Util/Cron/TProcessCronTask.php`
**Namespace:** `Prado\Util\Cron`
**Extends:** [`TCronTask`](TCronTask.md)
**Since:** 4.4.0

## Overview
`TProcessCronTask` keeps a background process running. Scheduled every minute, each run checks whether the managed process is alive and **(re)launches it when it is not**, a lightweight supervisor for a long-running CLI server:

```xml
<job name="websocket" schedule="* * * * *"
     task="Prado\Util\Cron\TProcessCronTask" Command="@php prado-cli websocket-server" />
```

Liveness is tracked with a **PID file**: `startProcess()` writes the launched pid and its start time to `getPidFile()`, and each run reads it and checks [`TProcessHelper::isRunning()`](../Helpers/TProcessHelper.md). The default PID file is `<runtime>/bg/<task name>.pid` (the `PID_DIRECTORY` constant), so managed processes keep their pid files in one folder.

- `@php` as a whole token in `Command` is replaced with `PHP_BINARY`.
- The process is launched **detached**: POSIX `nohup … >> log 2>&1 &` (pid via `echo $!`); Windows a hidden `Start-Process cmd.exe /c …` whose wrapper pid is tracked as the liveness proxy.
- Output goes to `LogFile` (default `/dev/null` / `NUL`); `Directory` is the working directory of the launch.
- Pid reuse is guarded by the recorded start time of the launched process (`ps -o lstart` / `Get-Process … StartTime`): a live pid with another start time is a different process. `Match`, a substring of the command line, is an additional guard (POSIX only).
- An advisory `flock()` on `<pidfile>.lock` prevents two overlapping sweeps from launching twice; the pid file is written atomically (temp + rename).
- A command that exits within the launch grace period (`LAUNCH_GRACE_USEC`, a `TAsync::sleep()` that yields to other cron fibers) is reported as a launch failure, not as started.

## Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `Command` | `?string` | `null` | The command that runs the process. Required. `@php` → `PHP_BINARY`. |
| `PidFile` | `string` | `<runtime>/bg/<name>.pid` | Where the launched pid and start time are recorded. |
| `Match` | `?string` | `null` | Command-line substring a running pid must contain (additional pid-reuse guard, POSIX). |
| `Directory` | `?string` | `null` | Working directory to launch from. |
| `LogFile` | `?string` | `null` → `/dev/null` | File the process output is redirected to. |

Inherited from `TCronTask`: `Name`, `Schedule`, `UserName`, `ModuleId`, `AutoRemove`, `ProcessCount`, `LastExecTime`, `Timeout`.

## Methods

| Method | Description |
|--------|-------------|
| `__construct(?string $command = null)` | Optionally sets the command. |
| `execute(TCronModule $cron): false\|int` | Ensures the process runs; returns `false` when already running, when another sweep holds the lock, or when the launch fails (logged at ERROR), else the launched pid. Throws when no `Command`. |
| `isProcessRunning(): bool` | True when the recorded pid is alive, has the recorded start time, and matches `Match` (if set). |
| `startProcess(): int` | Launches the process detached, records the pid, and confirms it survived the grace period. Throws on launch failure. |
| `getPid(): ?int` | The recorded pid, or null when the pid file is absent/invalid. |
| `launchProcess(string $command): ?int` | Protected. OS-specific detached launch, the single overridable seam (tests stub it). |
| `isRunning(int $pid): bool` / `processStartTime(int $pid): ?string` | Protected liveness and start-time seams around `TProcessHelper` and `ps`/PowerShell. |
| `verifyProcess(int $pid): bool` | Protected. Matches `Match` against the live command line (POSIX). |
| `acquireLock()` / `releaseLock($handle)` | Protected. The advisory lock around check-and-launch. |

## Notes
- Registered in `framework/classes.php` as `TProcessCronTask`.
- Error codes: `processcrontask_no_command`, `processcrontask_launch_failed`, `processcrontask_write_failed`.
- Intended to run in the cron **worker** (`prado-cli cron`), not a web request: it spawns OS processes.
- Windows pid tracking follows the `cmd.exe` wrapper that runs the command; the command-line `Match` check is POSIX-only.
- Default-valued properties are excluded from serialization (`_getZappableSleepProps`).
