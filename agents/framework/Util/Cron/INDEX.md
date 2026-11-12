# Util/Cron/INDEX.md

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / **`Cron`**

## Purpose

Cron-style scheduled task engine for the Prado framework. Supports both file-configured and database-backed task scheduling.

## Classes

- **`TCronModule`** — Main cron scheduler [`TModule`](../../TModule.md). Parses cron schedule expressions (standard Linux `minute hour day month dayOfWeek` format plus special expressions like `@daily`, `@hourly`). Registers tasks and triggers execution. Designed to be called periodically by the system crontab (the `prado-cli cron/run` shell action). Runs each due task in a fiber of its own as the task's user (`TFiberContext`) by the task's `Mode`: `Serial` alone, `Parallel` up to `Concurrency` at once, or `Process` in a child `cron/exec` process; with a `TaskTimeout` deadline, a run lock unless the task's `Overlap`, an exception boundary, and a recorded result per run. Cron processes publish their live status (`EnableStatus`, `getStatuses()`), which `prado-cli cron/top` shows (4.4.0).

- **`TDbCronManager`** — Extends `TCronModule` with database-backed task storage. Supports dynamic task CRUD, execution logging, runtime task queuing (onEndRequest), and permission-gated CLI management. **Since 4.3.3.**

- **`TDbCronModule`** — Deprecated alias for `TDbCronManager` (since 4.3.3). Scheduled for removal in v4.4. Existing configs continue to work.

- **`TCronTask`** — Abstract base for cron tasks. Implement `execute($cronModule)`. Properties: `Name`, `Schedule`, `UserName`, `Timeout`, `Mode`, `Overlap`, `TimeZone`, `StartingDeadline`, `Description`, the ping URLs, `Retries`, `Between`, `Jitter`, the `Process*` child settings, `WarnDuration`, `Priority`, and the chains `Then`/`ThenOnFailure`.

- **`TCronTaskMode`** — The `TEnumerable` of a task's `Mode`: `Serial`, `Parallel`, `Process`. **Since 4.4.0.**

- **[`TCronTaskEventParameter`](TCronTaskEventParameter.md)** — Parameter of the task events `onTaskStart` (settable `Cancel`), `onTaskEnd`, `onTaskFailure`, `onTaskSkipped` (`Reason`), and `onTaskPing` (settable `Handled`). **Since 4.4.0.**

- **`TCronTaskOverlap`** — The `TEnumerable` of a task's `Overlap`: `Skip`, `Allow`, `Replace`, `Queue`. **Since 4.4.0.**

- **[`TCompoundCronTask`](TCompoundCronTask.md)** — Concrete task that runs several steps (task definitions or `@name` references) as one run, in order or in parallel. **Since 4.4.0.**

- **[`TCronMonitorResponse`](TCronMonitorResponse.md)** — The monitoring endpoint: a `TJsonService` provider of the module's report as JSON or its Prometheus metrics. **Since 4.4.0.**

- **[`THttpCronTask`](THttpCronTask.md)** — Concrete task that sends an HTTP request through `THttpClient`; a non-2xx status is an error. **Since 4.4.0.**

- **[`TCommandCronTask`](TCommandCronTask.md)** — Concrete task that runs a shell command to completion: `@php`, `Directory`, `LogFile` (`AppendLog`), a nonzero exit code is an error, and the command is terminated when the run is cancelled at its Timeout. **Since 4.4.0.**

- **`TCronMethodTask`** — Concrete task that calls a method on a module or class. Method and parameters are configured externally.

- **`TClosureCronTask`** — Concrete task that runs a PHP `\Closure`. The closure is stored via [`TSerializableClosure`](../TSerializableClosure.md) and encrypted with a [`TSecurityManager`](../../Security/TSecurityManager.md). `StoreRaw` property: `false` (default, encrypted), `true` (raw/decrypted), or `'Debug'` (raw only in Debug mode). `SecurityManagerID` property: `null`/`''` uses the application security manager. **Since 4.4.0.**

- **[`TProcessCronTask`](TProcessCronTask.md)** — Concrete task that **keeps a background process running**: scheduled every minute, it (re)launches the `Command` (e.g. `@php prado-cli websocket-server`) whenever the PID file (`<runtime>/bg/<name>.pid`) shows it has died. Detached launch, `@php` → `PHP_BINARY`, an advisory lock against concurrent launches, a recorded start time (plus optional `Match`) as the pid-reuse guard, and a post-launch liveness check. **Since 4.4.0.**

- **`TCronTaskInfo`** — Task metadata: name, schedule, task class, last run time.

- **`TTimeScheduler`** — Cron expression parser. Supports multi-language expressions (8 languages) and special tokens (`@daily`, `@weekly`, `@monthly`, `@yearly`, `@annually`, `@hourly`, `@midnight`, `@{time()}`). `getNextTriggerTime($lastRun)` calculates the next execution timestamp.

- **`TDbCronCleanLogTask`** — Pre-built task that auto-purges old cron log entries from the database.

- **`TShellCronAction`** — CLI action listing the tasks (`prado-cli cron`, the default action since 4.4.0), running the pending ones (`prado-cli cron/run`), the live view (`cron/top`), a task now (`cron/now`), the next triggers (`cron/next`), and a Process task's child (`cron/exec`).

- **`TShellDbCronAction`** — CLI action for managing database-backed cron tasks.

- **`TShellCronLogBehavior`** — Behavior that adds execution logging to cron tasks.

- **[`Wake/`](Wake/INDEX.md)** — The wake transports (`Prado\Util\Cron\Wake`).

- **[`ICronWakeTransport`](Wake/ICronWakeTransport.md)** — The wake seam: `signal()` from a process that schedules a task, `wait()` for a per-minute cron process, `watch()`/`unwatch()` loop callbacks for the cron daemon, `close()`. Implementations: [`TCronWakeSocket`](Wake/TCronWakeSocket.md) (same host, datagram), [`TCronWakeSignal`](Wake/TCronWakeSignal.md) (same host, `SIGUSR1` to the pid file's process), [`TCronWakeFifo`](Wake/TCronWakeFifo.md) (same host, named pipe), [`TCronWakeFile`](Wake/TCronWakeFile.md) (hosts sharing a file system), [`TCronWakeCache`](Wake/TCronWakeCache.md) (clients of a cache), [`TCronWakePgsql`](Wake/TCronWakePgsql.md) (hosts sharing a PostgreSQL database, `LISTEN`/`NOTIFY`); the file and cache transports poll a stamp through [`TCronWakePoll`](Wake/TCronWakePoll.md). **Since 4.4.0.**

## Wake Transports and Second-Resolution Polling

`TDbCronManager` with `PollsPerMinute > 1` (or `SecondResolution`) services sub-minute slots within a single per-minute cron-tab invocation. `TCronModule::runPendingTasksWithWake()` polls the earliest pending task, waits until its trigger via `waitForWake()`, and **re-polls after every wake** so a task added or rescheduled mid-minute is honored. The claim in `updateTaskInfo()` advances a task's next execution time *before* running it, so an overlapping next-minute cron process, or another host, never re-runs it.

A scheduling change wakes a waiting cron process at once: `TDbCronManager::addTask`/`updateTask` call `signalWakeIfEarliest()`, which calls `TCronModule::signalWake()` only when the task becomes the earliest pending task **due within the current minute**. The wake goes through the module's `WakeTransport` ([`ICronWakeTransport`](Wake/ICronWakeTransport.md), a module ID or a `<wake>` child element of the cron module): the default [`TCronWakeSocket`](Wake/TCronWakeSocket.md), [`TCronWakeSignal`](Wake/TCronWakeSignal.md), and [`TCronWakeFifo`](Wake/TCronWakeFifo.md) reach the processes of one host; [`TCronWakeFile`](Wake/TCronWakeFile.md) and [`TCronWakeCache`](Wake/TCronWakeCache.md) reach other hosts through a shared file system or cache at their `PollInterval`, [`TCronWakePgsql`](Wake/TCronWakePgsql.md) through PostgreSQL `LISTEN`/`NOTIFY`, [`TCronWakeOracle`](Wake/TCronWakeOracle.md) through Oracle `DBMS_ALERT`, and [`TCronWakeRedis`](Wake/TCronWakeRedis.md) through Redis Pub/Sub. A missing listener is a harmless no-op.

## Daemon Mode

With `Daemon="true"` the crontab tick `prado-cli cron/run` elects a daemon instead of sweeping once. `TCronModule::runDaemon()` acquires the `DaemonMutex` lease (`cron:daemon:<module id>`; a [`TFileMutex`](../Mutex/TFileMutex.md) by default, so one daemon per host; a [`TDbMutex`](../Mutex/TDbMutex.md) or [`TRedisMutex`](../Mutex/TRedisMutex.md) for one daemon per cluster, by module ID or as a `<mutex>` child element) and exits at once when another process holds it. The daemon sweeps the pending tasks through `runTasks()` (fibers, `Concurrency`, `TaskTimeout`), then waits on the event loop until the next trigger (`getNextPendingTime()`), a wake from the `WakeTransport` (`watch()`), or `DaemonIdle` seconds, and repeats. After `DaemonLifetime` (default one hour) it retires: it holds a `…:retiring` lock and keeps serving until a tick's process takes the `…:handoff` lock and waits for the lease, then hands the lease over without a gap, so the new process picks up deployed code and the module is never without a daemon. A changed configuration file (`DaemonWatchConfig`, `DaemonConfigFiles`), a changed `DaemonDependency` (an `ICacheDependency`, or `<dependency>` child elements), `retireDaemon()`, the global `fxRetireCronDaemon` event, a `requestDaemonRetire()` from another process, or `prado-cli cron/retire` retires the daemon the same way; SIGTERM/SIGINT, `stopDaemon()`, `fxStopCronDaemon`, `requestDaemonStop()`, or `cron/stop` end it at once, and `cron/daemon` shows its state (`getDaemonState()`). Every sweep raises `onSweepStart` (a handler changes the tasks about to run) and `onSweepEnd` (results) with a [`TCronSweepEventParameter`](TCronSweepEventParameter.md); every `on` event is also broadcast as a global `fx` event (`fxCronSweepStart`, `fxCronDaemonIdle`, ...). `TDbCronManager` closes its database connection while the daemon waits (`onDaemonIdle()`) and reopens it on the next query. `onDaemonStart`, `onDaemonIdle` (with the wait a handler changes), and `onDaemonEnd` are raised with a [`TCronDaemonEventParameter`](TCronDaemonEventParameter.md).

Several hosts may each run a daemon or per-minute sweeps against one cron table: `TDbCronManager::updateTaskInfo()` claims a run with `UPDATE ... WHERE nextexectime = <the value this process read>` and runs the task only when its row was affected, so one process runs each trigger.

**End-to-end test.** The unit tests drive `runDaemon()` in one process with a pinned clock. `tests/cron-daemon/run.php` (`composer crontest`, a CI step on Linux, macOS, and Windows) runs real `prado-cli cron/run` ticks every 3 s, with a crontab's minimal environment, against a throwaway SQLite application in the temp directory, and reads the daemon's `fxCronDaemonStart`/`fxCronDaemonEnd` and task runs from a JSON-lines log (`tests/cron-daemon/app/CronE2EProbe.php`, `CronE2ETask.php`). The `handoff` scenario checks lifetime handoffs, a daemon killed by SIGTERM (terminated on Windows) and replaced by the next tick, `cron/stop`, no 2-second trigger run twice or missed, and that a tick not elected exits at once. The `wake` scenario checks that a task `cron/add` schedules from another process runs at its second while the daemon idles for 30 s, through the default `TCronWakeSocket` (`udg`, or `udp` on Windows). The `process` scenario runs a 5-second Process task every 3 s beside a 2-second Serial task: the Process task runs in `cron/exec` children one at a time (the run lock skips the triggers that come while it runs), the Serial task keeps its schedule in the daemon, the daemon's status file lists the child, `cron/top` and `cron/tasks` show it, and the daemon waits for the child before it ends. The `features` scenario runs one daemon over every task feature, with a PHP web server (`php -S`) whose router records requests: pings over HTTP and through the `fxCronTaskPing` seam, failures and their failure pings, `Retries`, pause and resume, `StartingDeadline`, a `Between` window, an `except` date, `Jitter`, `Priority` within a sweep, a `Then` chain, a `TCompoundCronTask`, a `TCommandCronTask`, a `THttpCronTask`, an `after end` schedule, a Process task's `WarnDuration`, `ProcessOutputFile`, `ProcessPriority` (POSIX), and `ProcessMemoryLimit`, the `TCronMonitorResponse` JSON report and Prometheus metrics served by the app, `cron/next` in a task's zone, `cron/top`, and the results in `cron/log`. The `overlap` scenario checks `Replace` (each trigger stops the running child) and `Queue` (runs back to back) on Process tasks. The probe module records the task events (`fxCronTaskStart`, `fxCronTaskEnd`, `fxCronTaskRetry`, `fxCronTaskOverdue`, `fxCronTaskSkipped`, `fxCronTaskPing`, `fxCronSweepStart`). The timing checks assume an unloaded machine. A failed scenario prints its event log and keeps its directory.

## Runtime Tasks & `deferTask`

A **runtime task** runs at `TApplication::onEndRequest` so work is offloaded from the response (e.g. sending an email after the page renders). `TDbCronManager::addRuntimeTask()` queues a `TCronTask` and attaches `executeRuntimeTasks` to `onEndRequest`; because the task row stays **active** in the DB, a missed `onEndRequest` (CLI, a fatal, the user pressing stop) is run by the next shell cron — so the task is guaranteed to run, not best-effort.

`TDbCronManager::deferTask(\Closure $closure, ?string $name = null)` is the closure convenience: it wraps the closure in an auto-removing [`TClosureCronTask`](TClosureCronTask.md) due immediately (encrypted by default), persists it (the must-run fallback), and queues it as a runtime task (the onEndRequest fast path). It lives only on `TDbCronManager` — the base `TCronModule` lacks the per-task persistence that makes the fallback (and thus the must-run guarantee) possible.

## Cron Schedule Format

```
minute  hour  day  month  dayOfWeek
  *       *     *    *        *
```

- Each field accepts: `*` (any), specific values, ranges (`1-5`), step values (`*/5`), lists (`1,3,5`); an optional sixth field is the year.
- Special expressions: `@yearly`, `@annually`, `@monthly`, `@weekly`, `@daily`, `@hourly`, `@midnight`, `@noon`, `@minute`, `@{time()}`, `@now+N`.
- Expressions are case-insensitive and support 8 language name sets for month/day names.
- Seconds (4.4.0): `minute:second` on a minute element, `*:*/10 * * * *` is every 10 seconds; no seconds part means second 0.
- Hashed fields (4.4.0): `H` or `#` in a field is a stable value from the task name, so `#daily` tasks spread over the day; `H/15`, `H(0-2)`, and the `#hourly` to `#yearly@midnight` shortcuts.
- Periods (4.4.0): `every 10 seconds`, `every 2 days from 2026-12-01 08:00 until 2027-01-01`, `every first monday of next month`; the anchor is stored as `from @<unix time>`.
- See [`TTimeScheduler`](TTimeScheduler.md) for the grammar.

## Conventions

- Register tasks in `application.xml` under the cron module, or dynamically via [`TCronModule::addTask()`](TCronModule.md).
- The system crontab should call `php prado-cli.php <appdir> cron/run` on a regular interval (e.g., every minute); a bare `cron` lists the tasks (4.4.0).
- `TCronModule` tracks last-run times; it will not execute a task more frequently than its schedule allows.
- For database-backed tasks, `TDbCronManager` must have a [`TDbConnection`](../../Data/TDbConnection.md) configured.
