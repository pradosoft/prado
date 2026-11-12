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
| `DaemonLifetime` | `3600` | Seconds after which the daemon *retires*: it keeps serving but hands the lease to the next tick's process (a rolling restart after a deploy, with no gap); `0` for never. |
| `DaemonHandoffTimeout` | `10` | Seconds a tick's process waits for a retiring daemon to hand over the lease before giving up. |
| `DaemonWatchConfig` | `true` | The daemon fingerprints `DaemonConfigFiles` (path, mtime, size, content hash) at start and on each idle; a change retires it, so the next tick's process, which read the new configuration, takes over without a gap. |
| `DaemonConfigFiles` | the application configuration file | Paths or namespaces to watch, in an array or comma-separated; name an included configuration file here when cron is configured in one. |
| `DaemonIdle` | `60` | Maximum seconds between sweeps when no trigger or wake comes sooner; also the poll period of a `TDbCronManager` table changed by a host without a shared wake transport. |
| `DaemonDependency` | `null` | An [`ICacheDependency`](../../Caching/ICacheDependency.md), or `<dependency>` child elements; the daemon checks `getHasChanged()` on each idle and retires on a change, as for a configuration file. Any cache dependency watches its source: a file or directory, a global or application state, or a chain. |
| `DaemonMutex` | a `TFileMutex` | The lease, an [`IMutex`](../Mutex/IMutex.md), its module ID, or a `<mutex>` child element. The lock name is `getDaemonLockName()`, `cron:daemon:<module id>`. A file mutex excludes the processes of one host; a database or Redis mutex excludes every host's. |
| `WakeTransport` | a `TCronWakeSocket` | The [`ICronWakeTransport`](Wake/ICronWakeTransport.md), its module ID, or a `<wake>` child element, shared by per-minute waits (`waitForWake()`), the daemon (`watch()`), and `signalWake()`. |

`DEFAULT_CRON_USER`, `DEFAULT_DAEMON_LIFETIME`, `DEFAULT_DAEMON_IDLE`, and `DAEMON_MIN_WAIT` are read through `static::` (the unset properties take them in `init()`), so a subclass overrides a default by redefining the constant.

`runDaemon()` returns the number of tasks it ran, or 0 at once when a healthy daemon holds the lease.

**Retiring and hand-off.** Three locks of the `DaemonMutex` carry the protocol: the lease `cron:daemon:<id>`, `…:retiring`, which the daemon holds once its lifetime passed, and `…:handoff`, which a tick's process holds while it waits for the lease. A tick that finds the lease held probes the retiring lock: free means the daemon is healthy and the tick exits at once with no wake and no wait. Held means the daemon is retiring: the tick takes the handoff lock, sends a wake, and waits up to `DaemonHandoffTimeout` for the lease (`takeOverDaemon()`). The retiring daemon sees the handoff lock held at its next loop turn, finishes nothing new, releases the lease, and ends with reason `handoff`; the waiting tick acquires it within a mutex retry and is the daemon, already bound to the transport, so no wake or task falls into a gap. A retiring daemon with no tick serves on indefinitely, and a daemon that cannot release within the timeout is retried by the next tick. Retiring starts after `DaemonLifetime`, when a watched configuration file changes (`checkDaemonConfig()`, each idle), or on `retireDaemon()`, which a deployment hook or an `onDaemonIdle` handler calls to restart the daemon on new code or configuration without a gap. `stopDaemon()`, SIGTERM, and SIGINT still end the daemon at once; the gap until the next tick is the explicit price of an immediate stop. Each turn: `sweepTasks()` (`filterStaleTasks()`, `getPendingTasks()`, `runTasks()` with no minute limit), then `waitDaemon()` for `getDaemonWait()` seconds, the least of the time to `getNextPendingTime()`, `DaemonIdle`, and the remaining lifetime, never under `DAEMON_MIN_WAIT`. A wake (`wakeDaemon()`, from the transport's `watch()` callback) or `stopDaemon()` (also SIGTERM and SIGINT through the loop's `onSignal()` where `pcntl` exists) ends the wait; a wake during a sweep causes another sweep. The lease is released and the transport closed in a `finally`. `dyLogCronDaemonStart($lockName, $watching)` and `dyLogCronDaemonEnd($numtasks)` are raised for behaviors, and written to the shell by [`TShellCronLogBehavior`](TShellCronLogBehavior.md).

Application code hooks the daemon through three `on` events, each with a [`TCronDaemonEventParameter`](TCronDaemonEventParameter.md):

| Event | When | Parameter |
|---|---|---|
| `onDaemonStart` | after the lease is acquired, before the first sweep | `LockName`, `Watching` |
| `onDaemonIdle` | before each wait | `Tasks` of the sweep just finished, `Elapsed`, and `Wait`, which a handler changes with `setWait()` |
| `onDaemonEnd` | after the lease is released | `Tasks` in all, `Elapsed`, `Reason` (`handoff`, `stop`, `signal`, `error`; `lifetime` only when a daemon could not take the retiring lock) |

`TDbCronManager` overrides `onDaemonIdle()` to close its database connection before raising the event. A handler that wants the daemon to end calls `retireDaemon()` (hand-off, no gap) or `stopDaemon()` (at once).

### Sweep events (4.4.0)

Every pass of `processPendingTasks()`, per-minute or in the daemon, raises two events with a [`TCronSweepEventParameter`](TCronSweepEventParameter.md):

| Event | When | Parameter |
|---|---|---|
| `onSweepStart` | the pending tasks are collected, before any runs | `Tasks` (settable: remove, add, reorder, or edit the tasks about to run), `Daemon`, `Count` |
| `onSweepEnd` | the last task finished | `Tasks` as run, `Results` (name to whether it ran without an exception), `Count`, `Failed` |

`runTasks()` returns the per-task results that feed `Results`.

### Global `fx` events and requests (4.4.0)

Each `on` event of the module is also broadcast as a global event, so a module declares the method and is called with no configuration: `fxCronSweepStart`, `fxCronSweepEnd`, `fxCronDaemonStart`, `fxCronDaemonIdle`, `fxCronDaemonEnd`, each with the same parameter object. In the other direction a component in the daemon's process raises `fxRetireCronDaemon` or `fxStopCronDaemon`, which the cron module handles by calling `retireDaemon()` or `stopDaemon()`, without a reference to the module.

### Requests from another process (4.4.0)

`getDaemonState()` reports `running`, `retiring`, or null by probing the lease and retiring locks, from any process. `requestDaemonRetire($timeout)` holds the `…:retire` lock, wakes the daemon, and waits for the retiring lock to appear; `requestDaemonStop($timeout)` holds the `…:stop` lock and waits for the lease to be released. The daemon checks both request locks on every loop turn (`checkDaemonRequests()`). Both return true on acknowledgment, false on timeout (`DEFAULT_DAEMON_REQUEST_TIMEOUT`, 10 s), and null when no daemon runs. The shell exposes them as `cron/daemon`, `cron/retire`, and `cron/stop`.

```xml
<module id="cron" class="Prado\Util\Cron\TDbCronManager" Daemon="true" DaemonLifetime="1800" DaemonMutex="dbmutex" WakeTransport="cronwake" />
<module id="dbmutex" class="Prado\Util\Mutex\TDbMutex" ConnectionID="db" />
<module id="cronwake" class="Prado\Util\Cron\Wake\TCronWakeCache" CacheID="memcache" PollInterval="0.5" />
```

**Child elements.** A transport, mutex, or dependency only cron uses is configured in place, without a module ID, as a `<wake>`, `<mutex>`, or `<dependency>` child of the cron module (`readComponentConfiguration()`, through [`TModule`](../../TModule.md)'s `getConfigElements()`, `createConfigComponent()`, and `createElementComponent()`): `class` names the class, the other attributes are its properties, and a module is initialized with the element. The PHP form is the `wake`, `mutex`, or `dependency` key with `class` and `properties`, or a class name alone; the `dependency` key also takes a list. Several `<dependency>` elements are chained in a [`TChainedCacheDependency`](../../Caching/TChainedCacheDependency.md), so any change retires the daemon. An element without a class throws `module_config_class_required`; a class of the wrong type throws `cron_waketransport_invalid`, `cron_daemonmutex_invalid`, or `cron_daemondependency_invalid`. The element replaces the `WakeTransport`, `DaemonMutex`, or `DaemonDependency` property when both are given. A dependency reports a change against its own state from when it was created, at configuration.

```xml
<module id="cron" class="Prado\Util\Cron\TDbCronManager" Daemon="true">
    <wake class="Prado\Util\Cron\Wake\TCronWakeCache" CacheID="memcache" PollInterval="0.5" />
    <mutex class="Prado\Util\Mutex\TDbMutex" ConnectionID="db" />
    <dependency class="Prado\Caching\TFileCacheDependency" FileName="/srv/app/REVISION" />
</module>
```

```php
'cron' => [
    'class' => Prado\Util\Cron\TDbCronManager::class,
    'properties' => ['Daemon' => true],
    'wake' => ['class' => Prado\Util\Cron\Wake\TCronWakeCache::class, 'properties' => ['CacheID' => 'memcache', 'PollInterval' => 0.5]],
    'mutex' => ['class' => Prado\Util\Mutex\TDbMutex::class, 'properties' => ['ConnectionID' => 'db']],
    'dependency' => [
        ['class' => Prado\Caching\TFileCacheDependency::class, 'properties' => ['FileName' => '/srv/app/REVISION']],
        Prado\Caching\TApplicationStateCacheDependency::class,
    ],
],
```

### Task modes, the run lock, and the task fiber (4.4.0)

`processPendingTasks()` hands the due tasks to `runTasks()`, which starts each one in a fiber of its own through [`TAsync::run()`](../../IO/Async/TAsync.md). The task's `Mode` ([`TCronTaskMode`](TCronTask.md#mode-and-overlap-440)) decides how:

| Mode | Runs | The sweep |
|---|---|---|
| `Serial` (default) | alone in the cron process, after the running tasks finish | waits for it |
| `Parallel` | in a fiber beside the other Parallel tasks, at most `Concurrency` (default 1) at once | waits for it |
| `Process` | in a child `prado-cli --quiet=1 cron/exec <task>` process (`TProcess`, `proc_open`) | does not wait; `awaitProcessRuns()` does |

`awaitTasks()` waits on the loop (`TAsync::any()->await()`) for a slot. A Parallel task that pauses through `TAsync::sleep()` or awaits a `TFuture` yields to the other tasks; one that blocks in PHP code holds the process. The crontab minute limit applies to starting a task, not to one already running. A `cron/run` sweep returns after its Process tasks finish; a daemon releases the lease, then waits for its Process tasks before it ends.

`runTask($task)` runs the task in the current fiber and returns whether it ran without an exception:

| Step | Detail |
|---|---|
| User | `runAsTaskUser()` → `resolveTaskUser()`: the task's `UserName`, else `DefaultUserName`, from the `UserManager`; else a guest clone of the application user; without a user manager the current user. The user goes into [`TFiberContext`](../../IO/Async/TFiberContext.md) under `TApplication::USER_CONTEXT_KEY`, so `TApplication::getUser()` returns it inside the task and the application user is untouched. `onSetUser` is raised for the task user and again for the restored user. |
| Permission | a `dyRunTask` denial is logged, claims the run, and records `RESULT_DENIED`. |
| Deadline | a triggered run later than the task's `StartingDeadline` is skipped and records `RESULT_MISSED`. |
| Run lock | without the task's `Overlap`, the run holds `getTaskLockName($name)` (`<lease>:task:<name>`) in the `DaemonMutex`. When another run holds it, the trigger is skipped: `updateTaskInfo($task, false)` advances the task without counting a run. The default `TFileMutex` locks per host; a shared mutex (`TDbMutex`, `TRedisMutex`) locks across hosts. |
| Start | `onTaskStart`; a handler's `Cancel` skips the run. |
| Claim | `updateTaskInfo()`; a run another process claimed is skipped. |
| Run | `invokeTask()` in this fiber, or `runTaskProcess()` for a Process task. |
| Deadline | `$task->Timeout ?? $this->TaskTimeout` (seconds, null for none) becomes a [`TCancellationToken`](../../IO/Async/TCancellationToken.md) in `TCronTask::getCancellationToken()` for the run; a cooperative task passes it to its waits. A Process child that outlives it by `PROCESS_TIMEOUT_GRACE` (5 s) is terminated (SIGTERM, then SIGKILL): `RESULT_TIMEOUT`. |
| Boundary | An exception from `execute()` goes to `logCronTaskError()`: an ERROR log entry and `dyLogCronTaskError($task, $exception)` (written to the shell by [`TShellCronLogBehavior`](TShellCronLogBehavior.md)); the sweep continues. |
| Result | `updateTaskResult($task, $result, $seconds)` stores `RESULT_OK`, `RESULT_ERROR`, `RESULT_DENIED`, `RESULT_TIMEOUT`, or `RESULT_MISSED` and the run's seconds: in the global state (`lastResult`, `lastDuration`), or the `lastresult` and `lastduration` columns of `TDbCronManager`. `getTaskResults($fresh)` reads them with the last run time and count. |

The Process child is `getTaskProcessCommand($task)`: `PHP_BINARY` (under a web SAPI, `php` in `PHP_BINDIR`), `bin/prado-cli.php`, `-d=<application base path>`, `--quiet=1`, `cron/exec`, and the task name. Its standard input is the null device; it writes to the cron process's standard output and error. `cron/exec` runs `executeTask($task)`: the user, permission, and deadline of `runTask()` without the run lock, claim, or result, which the parent holds. Its exit code is the result: 0, `EXIT_ERROR` (1), `EXIT_NO_TASK` (2), or `EXIT_DENIED` (3).

```xml
<module id="cron" class="Prado\Util\Cron\TCronModule" Concurrency="4" TaskTimeout="300">
    <job Name="fetch" Schedule="*/5 * * * *" Task="MyApp\FeedFetchTask" Mode="Parallel" Timeout="60" />
    <job Name="report" Schedule="0 2 * * *" Task="MyApp\ReportTask" Mode="Process" Timeout="3600" />
    <job Name="index" Schedule="* * * * *" Task="MyApp\IndexTask" Overlap="true" />
</module>
```

### Task events and pings (4.4.0)

`runTask($task, $manual = false)` raises the task events with a [`TCronTaskEventParameter`](TCronTaskEventParameter.md), each mirrored as a global `fx` event:

| Event | When | Parameter |
|---|---|---|
| `onTaskStart` / `fxCronTaskStart` | before the claim | `Cancel`: a handler skips the run |
| `onTaskEnd` / `fxCronTaskEnd` | after a run | `Result`, `Duration`, `Exception` (a task in the cron process) |
| `onTaskFailure` / `fxCronTaskFailure` | after `onTaskEnd` when the result is not `ok` | as `onTaskEnd` |
| `onTaskSkipped` / `fxCronTaskSkipped` | a trigger that does not run | `Reason`: `denied`, `deadline`, `running` (run lock held), `cancelled`, `claimed` |
| `onTaskPing` / `fxCronTaskPing` | before each ping is sent | `Ping`, `Url`, `Result`, `Duration`; a handler sets `Handled` when it delivered the ping |

A started run ends with `onTaskEnd` or `onTaskSkipped`; `denied`, `deadline`, and `running` are raised without a start. `Manual` is true for `cron/now`.

**StartingDeadline.** A triggered run more than `TCronTask::StartingDeadline` seconds after its trigger (`isTaskPastDeadline()`) is skipped: the trigger is claimed without a run and the result is `RESULT_MISSED`. A manual run has no deadline.

**Pings.** `pingTask()` sends the task's `PingBeforeUrl` after the claim, then `PingAfterUrl` after every run, and `PingSuccessUrl` or `PingFailureUrl` by the result. Each ping raises `onTaskPing` first; when no handler set `Handled`, `sendPing()` sends an HTTP GET through `HttpClient` (`getHttpClient()`, default `THttpClient::create()`, or `setHttpClient()`). A module that delivers pings its own way, such as a webhook module, listens for `fxCronTaskPing` without configuration and sets `Handled`; a subclass overrides `sendPing()`. A failed or non-2xx ping is a WARNING log entry naming the URL's host and does not change the run. The pings run in the task's fiber in the cron process, for a Process task too.

```xml
<job Name="backup" Schedule="0 2 * * *" Task="Prado\Util\Cron\TCommandCronTask" Command="@php bin/backup.php"
     TimeZone="America/New_York" StartingDeadline="3600" Description="Nightly database backup"
     PingBeforeUrl="https://hc-ping.com/UUID/start" PingSuccessUrl="https://hc-ping.com/UUID" PingFailureUrl="https://hc-ping.com/UUID/fail" />
```

### Live status (4.4.0)

With `EnableStatus`, a cron process publishes its live status to a JSON file in `StatusPath` (default `cronstatus` in the runtime path), named by module ID, host, and process ID. `cron/run` turns it on when the property is unset (null); set `EnableStatus="false"` to keep it off. The file is written through a temporary file renamed over it, on each run's start and end and before each daemon wait, and removed when the process ends. A write failure is a WARNING log entry.

`getStatuses()` reads the statuses of the module's processes:

| Key | Content |
|---|---|
| `role`, `pid`, `host`, `php`, `started`, `updated`, `concurrency` | the process (`STATUS_ROLE_DAEMON` or `STATUS_ROLE_SWEEP`) |
| `daemon` | null for a sweep; `state` (`running`, `retiring`, or `ending` while it waits for its Process tasks), `lock`, `transport`, `watching`, `lifetime`, `idle`, `sweeps`, `tasks`, `wake` (the next sweep's time), `next` (the next triggers) |
| `running` | each run: `name`, `mode`, `user`, `started`, `deadline`, `pid` (the child of a Process task) |
| `recent` | the last `STATUS_RECENT` (20) finished runs, newest first, with `result` and `duration` |
| `alive` | a process of this host is checked by its ID (`EPERM` counts as running); another host's is alive until `STATUS_STALE` (120 s) past its expected update |

A process that starts publishing removes the status files of this host's ended processes. `getNextTriggers($limit, $fresh)` and `getTaskResults($fresh)` read the stored run information (`$fresh` reloads the global state from the state persister; `TDbCronManager` always reads its rows), which `cron/top` uses to stay current.

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

# Show the cron processes, running tasks, next triggers, and last results; a terminal redraws
# every [seconds] (default 1) until Ctrl-C or [count] views; otherwise one view:
php prado-cli.php /path/to/app cron/top [seconds] [count]

# Run one task in this process (the child of a Process task):
php prado-cli.php /path/to/app cron/exec <name>

# Run a task now, whatever its schedule, and show its result:
php prado-cli.php /path/to/app cron/now <name>

# Show the next trigger times, one per task, or [count] (default 5) for a name filter:
php prado-cli.php /path/to/app cron/next [name] [count]

# Manage DB cron tasks and the log (TDbCronManager):
php prado-cli.php /path/to/app cron/add <name> <task-id> <schedule> [properties...]
php prado-cli.php /path/to/app cron/update <name> [properties...]
php prado-cli.php /path/to/app cron/remove <name>
php prado-cli.php /path/to/app cron/log [name] [@user]
php prado-cli.php /path/to/app cron/flush [period] [name] [@user]
php prado-cli.php /path/to/app cron/migrate
```

## Patterns & Gotchas

- **System crontab is required** — `TCronModule` itself doesn't schedule anything; the OS crontab must call `prado-cli cron/run` periodically. In daemon mode the tick is the daemon's liveness check: it exits when a daemon holds the lease and becomes the daemon otherwise.
- **Last-run tracking** — stored in application global state. If global state is cleared, tasks may run again immediately.
- **User context** — tasks can run as a specific user via `UserName` property (requires `TUserManager` to be configured). The user is the task fiber's user in `TFiberContext`, so a task that calls `$app->setUser()` changes only its own fiber.
- **A task future never rejects** — `runTask()` catches the task's exception; a rejection of the `TAsync::run()` future means `logCronTaskError()` itself threw.
- **Deadlines are cooperative** — a task that blocks in PHP code is not interrupted by its `Timeout`; only a wait that takes the token ends. A Process task's child is terminated after its timeout and `PROCESS_TIMEOUT_GRACE`.
- **Concurrency applies to Parallel tasks only** — Serial tasks (the default) run alone whatever `Concurrency` is.
- **A Process task is found by name in the child** — a configured task or a `TDbCronManager` row; an in-memory runtime task (`addRuntimeTask()` without a row) is not found, and its run is `RESULT_ERROR`.
- **A request cron waits for its Process tasks** — `processPendingTasks()` on `OnEndRequest` returns after the children exit.
- **No fork mode** — a Process task starts a fresh PHP process; forking the cron process would share its database connections, locks, and wake transport with the child.
- **`fxGetCronTaskInfos`** — the standard hook for modules to self-register maintenance tasks. Always prefer this over manual task registration.
