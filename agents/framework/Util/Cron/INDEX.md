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

- **`TCronWakeSocket`** — Same-host wake transport for second-resolution polling. A `udg` (unix-domain datagram), falling back to `udp` loopback, keyed by the **parity** of the serviced minute (even/odd) so a task overrunning into the next minute never contends for its socket. The socket stays **bound for the whole serviced minute** (`ensureBound`/`close`), so a datagram sent while the process is busy — mid-task or in the gap between a re-poll and the next wait — is **buffered** and consumed by the next wait instead of dropped. The cron process waits on it in `TCronModule::waitForWake()`; a web request that schedules an earlier task wakes it via `TCronModule::signalWake()`. **Since 4.4.0.**

## Second-Resolution Wake

`TDbCronManager` with `PollsPerMinute > 1` (or `SecondResolution`) services sub-minute slots within a single per-minute cron-tab invocation. `TCronModule::runPendingTasksWithWake()` polls the earliest pending task, waits until its trigger via `waitForWake()`, and **re-polls after every wake** so a task added or rescheduled mid-minute is honored. `updateTaskInfo` advances a task's next execution time *before* running it, so an overlapping next-minute cron process never re-runs it (overlap is allowed and dedup-safe — no lock).

A scheduling change wakes a sleeping cron immediately: `TDbCronManager::addTask`/`updateTask` call `signalWakeIfEarliest()`, which sends a wake datagram (via `TCronWakeSocket`) only when the task becomes the earliest pending task **due within the current minute**. A missing listener (no cron sleeping) is a harmless no-op. Cross-host wake (a shared-cache dirty token) is a planned follow-up; multi-host currently degrades to minute-granularity polling.

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
