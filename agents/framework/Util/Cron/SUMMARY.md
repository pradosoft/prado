# Util/Cron/SUMMARY.md

Cron-style scheduled task engine supporting both file-configured and database-backed task scheduling.

## Classes

- **`TCronModule`** — Main cron scheduler `TModule`; parses cron schedule expressions (standard Linux `minute hour day month dayOfWeek` format); runs each due task in its own fiber, `Concurrency` at once, with a per-task deadline and exception boundary; an `IClock` whose `time()`/`microtime()` drive the scheduling.

- **`TDbCronManager`** — Extends `TCronModule` with database-backed task storage, execution logging, runtime tasks (`deferTask`), and sub-minute polling (`PollsPerMinute`).

- **`TCronTask`** — Abstract base for cron tasks; implement `execute($cronModule)`; properties: `Name`, `Schedule`, `UserName`, `ModuleId`, `AutoRemove`, `Timeout`.

- **`TCronMethodTask`** — Concrete task that calls a method on a module or class.

- **`TClosureCronTask`** — Concrete task that runs a serialized (signed, by default encrypted) `\Closure` with a `Data` store.

- **`TProcessCronTask`** — Concrete task that keeps a background process running through a PID file.

- **`TCronTaskInfo`** — Task metadata: name, schedule, task class, last run time.

- **`TTimeScheduler`** — Cron expression parser supporting multi-language expressions and special tokens (`@daily`, `@hourly`, `@now`, etc.); both day fields restricted match as a union (POSIX).

- **`TCronWakeSocket`** — Same-host datagram transport that wakes a sleeping second-resolution cron process when an earlier task is scheduled.

- **`TDbCronCleanLogTask`** — Pre-built task that auto-purges old cron log entries from the database.

- **`TShellCronAction`** — CLI action listing the tasks (`prado-cli cron`) and running the pending ones (`prado-cli cron/run`).

- **`TShellDbCronAction`** — CLI action for managing database-backed cron tasks and the cron log.

- **`TShellCronLogBehavior`** — Behavior that adds execution logging to cron tasks.
