# Util/Cron/TCronModule

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TCronModule`**

## Class Info
**Location:** `framework/Util/Cron/TCronModule.php`
**Namespace:** `Prado\Util\Cron`

## Overview
Cron-style scheduled task engine. `TCronModule` manages task registration and execution timing. Tasks run when `prado-cli cron/run` is invoked by the system crontab (typically every minute). The module tracks last-run times so it won't run a task more frequently than its schedule allows.

## Architecture

```
system crontab (every minute)
    → prado-cli cron/run
        → [TShellCronAction](TShellCronAction.md)::run()
            → [TCronModule](TCronModule.md)::processPendingTasks()
                → evaluates each task's [TTimeScheduler](TTimeScheduler.md)
                → runs due tasks
```

## TCronModule

Register in `application.xml`:

```xml
<modules>
    <module id="cron" class="Prado\Util\Cron\TCronModule">
        <task name="cleanup" class="MyCleanupTask" Schedule="0 2 * * *" />
        <task name="report"  class="Prado\Util\Cron\TCronMethodTask"
              ModuleClass="reporting" MethodName="sendDailyReport"
              Schedule="30 8 * * 1-5" />
    </module>
</modules>
```

**PHP equivalent:**
```php
return [
    'modules' => [
        'cron' => [
            'class' => 'Prado\Util\Cron\TCronModule',
            'properties' => ['DefaultUserName' => 'cron'],
            'tasks' => [
                ['name' => 'cleanup', 'task' => 'MyApp\CleanupTask', 'schedule' => '0 2 * * *'],
            ],
        ],
    ],
];
```

### Key Methods

```php
$cron = $app->getModule('cron');
$cron->addTask($task);                    // register a TCronTask dynamically
$cron->processPendingTasks();             // check and run due tasks
$cron->getTaskInfos();                    // array of TCronTaskInfo

// Filtered lookup — returns array<string, T> keyed by task name (@template):
$cron->getTasksByType(TDbCronCleanLogTask::class);
```

**Clock seam (`@since 4.4.0`)** — `TCronModule` is an [`IClock`](../Clock/IClock.md) via [`TClockTrait`](../Clock/TClockTrait.md). Cron scheduling reads time through `time()` and `microtime()`; in production these are the real system clock. The cron test harness (`tests/unit/Harness/Util/Cron`) overrides `now()`, `time()`, and `microtime()` to drive scheduling deterministically. The remaining environment seams are protected overridables: `mt_rand()`, `php_sapi_name()`, `time_sleep_until()` (a `sleep()` on the event loop), and `waitForWake()`. For code that *holds* a clock rather than being one, use [`TClockAwareTrait`](../Clock/TClockAwareTrait.md).

### Daemon mode (4.4.0)

`Daemon="true"` turns the crontab tick `cron/run` into a daemon election: `processPendingTasks()` in the CLI calls `runDaemon()`.

| Property | Default | Description |
|---|---|---|
| `Daemon` | `false` | `cron/run` in the CLI elects a daemon instead of sweeping once; a web request (`EnableRequestCron`) still sweeps once. Frozen after `init()`. |
| `DaemonLifetime` | `3600` | Seconds the daemon runs before it exits so the next tick elects a new one (a rolling restart after a deploy); `0` for no limit. |
| `DaemonIdle` | `60` | Maximum seconds between sweeps when no trigger or wake comes sooner; also the poll period of a `TDbCronManager` table changed by a host without a shared wake transport. |
| `DaemonMutex` | a `TFileMutex` | The lease, an [`IMutex`](../Mutex/IMutex.md) or its module ID. The lock name is `getDaemonLockName()`, `cron:daemon:<module id>`. A file mutex excludes the processes of one host; a database or Redis mutex excludes every host's. |
| `WakeTransport` | a `TCronWakeSocket` | The [`ICronWakeTransport`](Wake/ICronWakeTransport.md) or its module ID, shared by per-minute waits (`waitForWake()`), the daemon (`watch()`), and `signalWake()`. |

`runDaemon()` returns the number of tasks it ran, or 0 at once when the lease is held. Each turn: `sweepTasks()` (`filterStaleTasks()`, `getPendingTasks()`, `runTasks()` with no minute limit), then `waitDaemon()` for `getDaemonWait()` seconds, the least of the time to `getNextPendingTime()`, `DaemonIdle`, and the remaining lifetime, never under `DAEMON_MIN_WAIT`. A wake (`wakeDaemon()`, from the transport's `watch()` callback) or `stopDaemon()` (also SIGTERM and SIGINT through the loop's `onSignal()` where `pcntl` exists) ends the wait; a wake during a sweep causes another sweep. `onDaemonIdle()` runs before each wait; `TDbCronManager` closes its database connection there. The lease is released and the transport closed in a `finally`. `dyLogCronDaemonStart($lockName, $watching)` and `dyLogCronDaemonEnd($numtasks)` are raised, and written to the shell by [`TShellCronLogBehavior`](TShellCronLogBehavior.md).

```xml
<module id="cron" class="Prado\Util\Cron\TDbCronManager" Daemon="true" DaemonLifetime="1800" DaemonMutex="dbmutex" WakeTransport="cronwake" />
<module id="dbmutex" class="Prado\Util\Mutex\TDbMutex" ConnectionID="db" />
<module id="cronwake" class="Prado\Util\Cron\TCronWakeCache" CacheID="memcache" PollInterval="0.5" />
```

### Concurrency and the task fiber (4.4.0)

`processPendingTasks()` hands the due tasks to `runTasks()`, which starts each one in a fiber of its own through [`TAsync::run()`](../../IO/Async/TAsync.md) and keeps at most `Concurrency` (default 1) running; `awaitTasks()` waits on the loop (`TAsync::any()->await()`) for a slot. A task that pauses through `TAsync::sleep()` or awaits a `TFuture` yields to the other tasks; a task that never waits runs to completion before the next starts, as before. The crontab minute limit applies to starting a task, not to one already running.

`runTask($task)` runs the task in the current fiber and returns whether it ran without an exception:

| Step | Detail |
|---|---|
| User | `resolveTaskUser()` → the task's `UserName`, else `DefaultUserName`, from the `UserManager`; else a guest clone of the application user; without a user manager the current user. The user goes into [`TFiberContext`](../../IO/Async/TFiberContext.md) under `TApplication::USER_CONTEXT_KEY`, so `TApplication::getUser()` returns it inside the task and the application user is untouched. `onSetUser` is raised for the task user and again for the restored user. |
| Deadline | `$task->Timeout ?? $this->TaskTimeout` (seconds, null for none) becomes a [`TCancellationToken`](../../IO/Async/TCancellationToken.md) in `TCronTask::getCancellationToken()` for the run; a cooperative task passes it to its waits. The token is cleared after the run, which cancels its timer. |
| Boundary | An exception from `execute()` goes to `logCronTaskError()`: an ERROR log entry and `dyLogCronTaskError($task, $exception)` (written to the shell by [`TShellCronLogBehavior`](TShellCronLogBehavior.md)); the sweep continues. `logCronTask()` and `updateTaskInfo()` run outside the boundary. |

```xml
<module id="cron" class="Prado\Util\Cron\TCronModule" Concurrency="4" TaskTimeout="300">
    <job Name="slow" Schedule="*/5 * * * *" Task="MyApp\SlowTask" Timeout="60" />
</module>
```

### fxGetCronTaskInfos

`[TCronModule](TCronModule.md)` fires global event `fxGetCronTaskInfos` during init. Modules can respond with an array of `[TCronTaskInfo](TCronTaskInfo.md)` to auto-register their own maintenance tasks (e.g., `TDbCache` registers a cleanup task this way).

```php
public function fxGetCronTaskInfos($sender, $param): array
{
    return [new TCronTaskInfo('cleanup', 'MyCleanupTask', '0 2 * * *')];
}
```

## TDbCronManager

Extends `TCronModule` with database storage for tasks and execution logs. `TDbCronModule` is a deprecated alias for `TDbCronManager` (since 4.3.3).

```xml
<modules>
    <module id="cron" class="Prado\Util\Cron\TDbCronManager"
            ConnectionID="db" />
</modules>
```

Additional features:
- Dynamic task CRUD (tasks persisted to DB)
- Execution logging with timestamps and output
- Runtime task queue (`addRuntimeTask` / `executeRuntimeTasks` on `onEndRequest`)
- `[TDbCronCleanLogTask](TDbCronCleanLogTask.md)` — pre-built task to purge old log entries
- Permission-gated CLI: `prado-cli db-cron` for managing DB-backed tasks

See [`TDbCronManager`](TDbCronManager.md) for full documentation.

## TCronTask (Abstract Base)

```php
class MyCleanupTask extends [TCronTask](TCronTask.md)
{
    public function execute([TCronTaskInfo](TCronTaskInfo.md) $info): void
    {
        // Do your work here
        $info->setLastResult('Cleaned 42 records');
    }
}
```

## TCronMethodTask

Calls a method on an existing module without writing a full task class:

```xml
<task name="report" class="Prado\Util\Cron\TCronMethodTask"
      ModuleClass="reporting" MethodName="generateDailyReport"
      Schedule="0 8 * * *" />
```

## TTimeScheduler — Schedule Expressions

Standard 5-field cron format:
```
minute  hour  day-of-month  month  day-of-week
  *       *         *          *        *
```

Fields support: `*` (any), specific values (`5`), ranges (`1-5`), steps (`*/15`), lists (`1,3,5`).

Special expressions:

| Expression | Meaning |
|------------|---------|
| `@hourly` | `0 * * * *` |
| `@daily` | `0 0 * * *` |
| `@weekly` | `0 0 * * 0` |
| `@monthly` | `0 0 1 * *` |
| `@yearly` | `0 0 1 1 *` |
| `@reboot` | Runs once on next cron call |

Multi-language month/day names supported (8 languages). Case-insensitive.

```php
$scheduler = new [TTimeScheduler](TTimeScheduler.md)();
$scheduler->setSchedule('0 2 * * *');
$nextRun = $scheduler->getNextTriggerTime($lastRunTimestamp);
```

## TCronTaskInfo

Value object with task metadata:
- `Name`, `Schedule`, `TaskClass`, `LastRunTime`, `LastResult`

## CLI Integration

```bash
# List tasks and their schedules (the default action, 4.4.0); filter by name and @user:
php prado-cli.php /path/to/app cron [name] [@user]

# Run all pending tasks (the system crontab entry):
php prado-cli.php /path/to/app cron/run

# Manage DB cron tasks and the log (TDbCronManager):
php prado-cli.php /path/to/app cron/add <name> <task-id> <schedule> [properties...]
php prado-cli.php /path/to/app cron/update <name> [properties...]
php prado-cli.php /path/to/app cron/remove <name>
php prado-cli.php /path/to/app cron/log [name] [@user]
php prado-cli.php /path/to/app cron/flush [period] [name] [@user]
```

## Patterns & Gotchas

- **System crontab is required** — `TCronModule` itself doesn't schedule anything; the OS crontab must call `prado-cli cron/run` periodically. In daemon mode the tick is the daemon's liveness check: it exits when a daemon holds the lease and becomes the daemon otherwise.
- **Last-run tracking** — stored in application global state. If global state is cleared, tasks may run again immediately.
- **User context** — tasks can run as a specific user via `UserName` property (requires `TUserManager` to be configured). The user is the task fiber's user in `TFiberContext`, so a task that calls `$app->setUser()` changes only its own fiber.
- **A task future never rejects** — `runTask()` catches the task's exception; a rejection of the `TAsync::run()` future means `logCronTaskError()` itself threw.
- **Deadlines are cooperative** — a task that blocks in PHP code is not interrupted by its `Timeout`; only a wait that takes the token ends.
- **`fxGetCronTaskInfos`** — the standard hook for modules to self-register maintenance tasks. Always prefer this over manual task registration.
