# Util/Cron/SUMMARY.md

Cron-style scheduled task engine supporting both file-configured and database-backed task scheduling.

## Classes

- **`TCronModule`** — Main cron scheduler `TModule`; parses cron schedule expressions (standard Linux `minute hour day month dayOfWeek` format); runs each due task in its own fiber, `Concurrency` at once, with a per-task deadline and exception boundary; an `IClock` whose `time()`/`microtime()` drive the scheduling; `Daemon` mode elects one long-running process per lease that waits on the event loop for the next trigger or a wake.

- **`TDbCronManager`** — Extends `TCronModule` with database-backed task storage, execution logging, runtime tasks (`deferTask`), sub-minute polling (`PollsPerMinute`), and a per-row run claim for several hosts.

- **`TCronTask`** — Abstract base for cron tasks; implement `execute($cronModule)`; properties: `Name`, `Schedule`, `UserName`, `ModuleId`, `AutoRemove`, `Timeout`.

- **`TCronMethodTask`** — Concrete task that calls a method on a module or class.

- **`TClosureCronTask`** — Concrete task that runs a serialized (signed, by default encrypted) `\Closure` with a `Data` store.

- **`TProcessCronTask`** — Concrete task that keeps a background process running through a PID file.

- **`TCronDaemonEventParameter`** — Parameter of the daemon events `onDaemonStart`, `onDaemonIdle` (settable `Wait`), and `onDaemonEnd` (`Reason`).

- **`TCronTaskInfo`** — Task metadata: name, schedule, task class, last run time.

- **`TTimeScheduler`** — Cron expression parser supporting multi-language expressions and special tokens (`@daily`, `@hourly`, `@now`, etc.); both day fields restricted match as a union (POSIX).

- **`ICronWakeTransport`** — The wake seam (`signal`, `wait`, `watch`); **`TCronWakeSocket`** (same-host datagram), **`TCronWakeSignal`** (process signal), **`TCronWakeFifo`** (named pipe), **`TCronWakeFile`** (shared file system), **`TCronWakeCache`** (shared cache, via **`TCronWakePoll`**), and **`TCronWakePgsql`** (PostgreSQL `LISTEN`/`NOTIFY`) wake a waiting cron process or daemon when an earlier task is scheduled.

- **`TDbCronCleanLogTask`** — Pre-built task that auto-purges old cron log entries from the database.

- **`TShellCronAction`** — CLI action listing the tasks (`prado-cli cron`) and running the pending ones (`prado-cli cron/run`).

- **`TShellDbCronAction`** — CLI action for managing database-backed cron tasks and the cron log.

- **`TShellCronLogBehavior`** — Behavior that adds execution logging to cron tasks.
