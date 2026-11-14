# Util/Cron/TCommandCronTask

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TCommandCronTask`**

## Class Info
**Location:** `framework/Util/Cron/TCommandCronTask.php`
**Namespace:** `Prado\Util\Cron`
**Extends:** [`TCronTask`](TCronTask.md)
**Since:** 4.4.0

## Overview
Runs a shell command to completion on each trigger. The run's result follows the command's exit code: 0 is `ok`, any other code throws a `TIOException` (`commandcrontask_failed`), so the run is an `error` and `onTaskFailure` and the failure ping follow. [`TProcessCronTask`](TProcessCronTask.md) keeps a background process alive instead.

```xml
<job name="backup" schedule="0 2 * * *" task="Prado\Util\Cron\TCommandCronTask"
     Command="@php bin/backup.php --full" LogFile="/var/log/app/backup.log" Timeout="3600" />
```

## Properties

| Property | Default | Meaning |
|---|---|---|
| `Command` | null | The command; `@php` as a whole word becomes the quoted `PHP_BINARY`. None throws `commandcrontask_no_command`. |
| `Directory` | null | The working directory; null keeps the cron process's. |
| `LogFile` | null | The file of the command's output and errors; null discards them (`/dev/null`, `NUL`). |
| `AppendLog` | true | Each run appends to `LogFile`; false starts it over. Errors always append. |
| `ExitCode` | null | Read-only: the exit code of the last run in this process; not serialized. |

## Behavior

- The command runs through the shell (`sh -c` on POSIX, `cmd /c` on Windows) through [`TProcess::open()`](../../IO/Process/TProcess.md), with standard input from the null device.
- `waitForProcess()` waits on the event loop (`TProcess::wait()`, a 50 ms poll and 250 ms cancellation checks), so a Parallel task yields to the others.
- When the run's `CancellationToken` is cancelled at its `Timeout`, the command is terminated (SIGTERM, then SIGKILL after `TERMINATE_GRACE`, 5 s) and the token's `TCancelledException` is thrown. The signal reaches the shell; `exec` before the command signals the command itself.

## See Also

- [TCronTask](TCronTask.md)
- [TProcessCronTask](TProcessCronTask.md)
- [TCronModule](TCronModule.md)
