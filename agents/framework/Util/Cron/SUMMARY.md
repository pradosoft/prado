# Util/Cron/SUMMARY.md

Cron-style scheduled task engine supporting both file-configured and database-backed task scheduling.

## Classes

- **`TCronModule`** — Main cron scheduler `TModule`; parses cron schedule expressions (standard Linux `minute hour day month dayOfWeek` format); runs each due task in its own fiber by its `Mode` (`Serial`, `Parallel` up to `Concurrency`, or `Process` in a child `cron/exec` process), with a per-task deadline, run lock, and exception boundary; publishes each cron process's live status for `cron/top`; an `IClock` whose `time()`/`microtime()` drive the scheduling; `Daemon` mode elects one long-running process per lease that waits on the event loop for the next trigger or a wake.

- **`TDbCronManager`** — Extends `TCronModule` with database-backed task storage, execution logging, runtime tasks (`deferTask`), second-precise crontab ticks, and a per-row run claim for several hosts.

- **`TCronTask`** — Abstract base for cron tasks; implement `execute($cronModule)`; properties: `Name`, `Schedule`, `UserName`, `ModuleId`, `AutoRemove`, `Timeout`, `Mode`, `Overlap`, `TimeZone`, `StartingDeadline`, `Description`, and the ping URLs.

- **`TCronTaskMode`** — The task `Mode` enumerable: `Serial`, `Parallel`, `Process`.

- **`TCronTaskEventParameter`** — Parameter of the task events `onTaskStart`, `onTaskEnd`, `onTaskFailure`, `onTaskSkipped`, and `onTaskPing`.

- **`TCronTaskOverlap`** — The task `Overlap` enumerable: `Skip`, `Allow`, `Replace`, `Queue`.

- **`TCompoundCronTask`** — Concrete task that runs several steps as one run.

- **`TCronMonitorResponse`** — The cron monitoring endpoint for `TJsonService`: a JSON report or Prometheus metrics.

- **`THttpCronTask`** — Concrete task that sends an HTTP request; a non-2xx status is an error.

- **`TCommandCronTask`** — Concrete task that runs a shell command to completion, with its output to a log file.

- **`TCronMethodTask`** — Concrete task that calls a method on a module or class.

- **`TClosureCronTask`** — Concrete task that runs a serialized (signed, by default encrypted) `\Closure` with a `Data` store.

- **`TProcessCronTask`** — Concrete task that keeps a background process running through a PID file.

- **`TCronDaemonEventParameter`** — Parameter of the daemon events `onDaemonStart`, `onDaemonIdle` (settable `Wait`), and `onDaemonEnd` (`Reason`).

- **`TCronSweepEventParameter`** — Parameter of the sweep events `onSweepStart` (settable `Tasks`) and `onSweepEnd` (`Results`, `Failed`).

- **`TCronTaskInfo`** — Task metadata: name, schedule, task class, last run time.

- **`TTimeScheduler`** — Cron expression parser supporting multi-language expressions and special tokens (`@daily`, `@hourly`, `@now`, etc.); both day fields restricted match as a union (POSIX).

- **`ICronWakeTransport`** — The wake seam (`signal`, `wait`, `watch`); **`TCronWakeSocket`** (same-host datagram), **`TCronWakeSignal`** (process signal), **`TCronWakeFifo`** (named pipe), **`TCronWakeFile`** (shared file system), **`TCronWakeCache`** (shared cache, via **`TCronWakePoll`**), and **`TCronWakePgsql`** (PostgreSQL `LISTEN`/`NOTIFY`) wake a waiting cron process or daemon when an earlier task is scheduled.

- **`TDbCronCleanLogTask`** — Pre-built task that auto-purges old cron log entries from the database.

- **`TShellCronAction`** — CLI action listing the tasks (`prado-cli cron`), running the pending ones (`cron/run`), the live view (`cron/top`), a task now (`cron/now`), the next triggers (`cron/next`), a Process task's child (`cron/exec`), and showing, retiring, or stopping the daemon (`cron/daemon`, `cron/retire`, `cron/stop`).

- **`TShellDbCronAction`** — CLI action for managing database-backed cron tasks and the cron log.

- **`TShellCronLogBehavior`** — Behavior that adds execution logging to cron tasks.
