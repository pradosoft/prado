# Util/Cron/INDEX.md

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / **`Cron`**

## Purpose

Cron-style scheduled task engine for the Prado framework. Supports both file-configured and database-backed task scheduling.

## Classes

- **`TCronModule`** — Main cron scheduler [`TModule`](../../TModule.md). Parses cron schedule expressions (standard Linux `minute hour day month dayOfWeek` format plus special expressions like `@daily`, `@hourly`). Registers tasks and triggers execution. Designed to be called periodically by the system crontab (the `prado-cli cron/run` shell action). Runs each due task in a fiber of its own as the task's user (`TFiberContext`), `Concurrency` tasks at once (default 1), with a `TaskTimeout` deadline and an exception boundary per task (4.4.0).

- **`TDbCronManager`** — Extends `TCronModule` with database-backed task storage. Supports dynamic task CRUD, execution logging, runtime task queuing (onEndRequest), and permission-gated CLI management. **Since 4.3.3.**

- **`TDbCronModule`** — Deprecated alias for `TDbCronManager` (since 4.3.3). Scheduled for removal in v4.4. Existing configs continue to work.

- **`TCronTask`** — Abstract base for cron tasks. Implement `execute(TCronTaskInfo)`. Properties: `Name`, `Schedule`. Methods: `init()`, `run()`.

- **`TCronMethodTask`** — Concrete task that calls a method on a module or class. Method and parameters are configured externally.

- **`TClosureCronTask`** — Concrete task that runs a PHP `\Closure`. The closure is stored via [`TSerializableClosure`](../TSerializableClosure.md) and encrypted with a [`TSecurityManager`](../../Security/TSecurityManager.md). `StoreRaw` property: `false` (default, encrypted), `true` (raw/decrypted), or `'Debug'` (raw only in Debug mode). `SecurityManagerID` property: `null`/`''` uses the application security manager. **Since 4.4.0.**

- **[`TProcessCronTask`](TProcessCronTask.md)** — Concrete task that **keeps a background process running**: scheduled every minute, it (re)launches the `Command` (e.g. `@php prado-cli websocket-server`) whenever the PID file (`<runtime>/bg/<name>.pid`) shows it has died. Detached launch, `@php` → `PHP_BINARY`, an advisory lock against concurrent launches, a recorded start time (plus optional `Match`) as the pid-reuse guard, and a post-launch liveness check. **Since 4.4.0.**

- **`TCronTaskInfo`** — Task metadata: name, schedule, task class, last run time.

- **`TTimeScheduler`** — Cron expression parser. Supports multi-language expressions (8 languages) and special tokens (`@daily`, `@weekly`, `@monthly`, `@yearly`, `@annually`, `@hourly`, `@midnight`, `@{time()}`). `getNextTriggerTime($lastRun)` calculates the next execution timestamp.

- **`TDbCronCleanLogTask`** — Pre-built task that auto-purges old cron log entries from the database.

- **`TShellCronAction`** — CLI action listing the tasks (`prado-cli cron`, the default action since 4.4.0) and running the pending ones (`prado-cli cron/run`).

- **`TShellDbCronAction`** — CLI action for managing database-backed cron tasks.

- **`TShellCronLogBehavior`** — Behavior that adds execution logging to cron tasks.

- **[`Wake/`](Wake/INDEX.md)** — The wake transports (`Prado\Util\Cron\Wake`).

- **[`ICronWakeTransport`](Wake/ICronWakeTransport.md)** — The wake seam: `signal()` from a process that schedules a task, `wait()` for a per-minute cron process, `watch()`/`unwatch()` loop callbacks for the cron daemon, `close()`. Implementations: [`TCronWakeSocket`](Wake/TCronWakeSocket.md) (same host, datagram), [`TCronWakeSignal`](Wake/TCronWakeSignal.md) (same host, `SIGUSR1` to the pid file's process), [`TCronWakeFifo`](Wake/TCronWakeFifo.md) (same host, named pipe), [`TCronWakeFile`](Wake/TCronWakeFile.md) (hosts sharing a file system), [`TCronWakeCache`](Wake/TCronWakeCache.md) (clients of a cache), [`TCronWakePgsql`](Wake/TCronWakePgsql.md) (hosts sharing a PostgreSQL database, `LISTEN`/`NOTIFY`); the file and cache transports poll a stamp through [`TCronWakePoll`](Wake/TCronWakePoll.md). **Since 4.4.0.**

## Wake Transports and Second-Resolution Polling

`TDbCronManager` with `PollsPerMinute > 1` (or `SecondResolution`) services sub-minute slots within a single per-minute cron-tab invocation. `TCronModule::runPendingTasksWithWake()` polls the earliest pending task, waits until its trigger via `waitForWake()`, and **re-polls after every wake** so a task added or rescheduled mid-minute is honored. The claim in `updateTaskInfo()` advances a task's next execution time *before* running it, so an overlapping next-minute cron process, or another host, never re-runs it.

A scheduling change wakes a waiting cron process at once: `TDbCronManager::addTask`/`updateTask` call `signalWakeIfEarliest()`, which calls `TCronModule::signalWake()` only when the task becomes the earliest pending task **due within the current minute**. The wake goes through the module's `WakeTransport` ([`ICronWakeTransport`](Wake/ICronWakeTransport.md)): the default [`TCronWakeSocket`](Wake/TCronWakeSocket.md), [`TCronWakeSignal`](Wake/TCronWakeSignal.md), and [`TCronWakeFifo`](Wake/TCronWakeFifo.md) reach the processes of one host; [`TCronWakeFile`](Wake/TCronWakeFile.md) and [`TCronWakeCache`](Wake/TCronWakeCache.md) reach other hosts through a shared file system or cache at their `PollInterval`, and [`TCronWakePgsql`](Wake/TCronWakePgsql.md) through PostgreSQL `LISTEN`/`NOTIFY`. A missing listener is a harmless no-op.

## Daemon Mode

With `Daemon="true"` the crontab tick `prado-cli cron/run` elects a daemon instead of sweeping once. `TCronModule::runDaemon()` acquires the `DaemonMutex` lease (`cron:daemon:<module id>`; a [`TFileMutex`](../Mutex/TFileMutex.md) by default, so one daemon per host; a [`TDbMutex`](../Mutex/TDbMutex.md) or [`TRedisMutex`](../Mutex/TRedisMutex.md) for one daemon per cluster) and exits at once when another process holds it. The daemon sweeps the pending tasks through `runTasks()` (fibers, `Concurrency`, `TaskTimeout`), then waits on the event loop until the next trigger (`getNextPendingTime()`), a wake from the `WakeTransport` (`watch()`), or `DaemonIdle` seconds, and repeats. After `DaemonLifetime` (default one hour) it retires: it holds a `…:retiring` lock and keeps serving until a tick's process takes the `…:handoff` lock and waits for the lease, then hands the lease over without a gap, so the new process picks up deployed code and the module is never without a daemon. A changed configuration file (`DaemonWatchConfig`, `DaemonConfigFiles`) or `retireDaemon()` retires the daemon the same way; SIGTERM/SIGINT or `stopDaemon()` end it at once. `TDbCronManager` closes its database connection while the daemon waits (`onDaemonIdle()`) and reopens it on the next query. `onDaemonStart`, `onDaemonIdle` (with the wait a handler changes), and `onDaemonEnd` are raised with a [`TCronDaemonEventParameter`](TCronDaemonEventParameter.md).

Several hosts may each run a daemon or per-minute sweeps against one cron table: `TDbCronManager::updateTaskInfo()` claims a run with `UPDATE ... WHERE nextexectime = <the value this process read>` and runs the task only when its row was affected, so one process runs each trigger.

## Runtime Tasks & `deferTask`

A **runtime task** runs at `TApplication::onEndRequest` so work is offloaded from the response (e.g. sending an email after the page renders). `TDbCronManager::addRuntimeTask()` queues a `TCronTask` and attaches `executeRuntimeTasks` to `onEndRequest`; because the task row stays **active** in the DB, a missed `onEndRequest` (CLI, a fatal, the user pressing stop) is run by the next shell cron — so the task is guaranteed to run, not best-effort.

`TDbCronManager::deferTask(\Closure $closure, ?string $name = null)` is the closure convenience: it wraps the closure in an auto-removing [`TClosureCronTask`](TClosureCronTask.md) due immediately (encrypted by default), persists it (the must-run fallback), and queues it as a runtime task (the onEndRequest fast path). It lives only on `TDbCronManager` — the base `TCronModule` lacks the per-task persistence that makes the fallback (and thus the must-run guarantee) possible.

## Cron Schedule Format

```
minute  hour  day  month  dayOfWeek
  *       *     *    *        *
```

- Each field accepts: `*` (any), specific values, ranges (`1-5`), step values (`*/5`), lists (`1,3,5`).
- Special expressions: `@yearly`, `@annually`, `@monthly`, `@weekly`, `@daily`, `@hourly`, `@midnight`, `@{time()}`.
- Expressions are case-insensitive and support 8 language name sets for month/day names.

## Conventions

- Register tasks in `application.xml` under the cron module, or dynamically via [`TCronModule::addTask()`](TCronModule.md).
- The system crontab should call `php prado-cli.php <appdir> cron/run` on a regular interval (e.g., every minute); a bare `cron` lists the tasks (4.4.0).
- `TCronModule` tracks last-run times; it will not execute a task more frequently than its schedule allows.
- For database-backed tasks, `TDbCronManager` must have a [`TDbConnection`](../../Data/TDbConnection.md) configured.
