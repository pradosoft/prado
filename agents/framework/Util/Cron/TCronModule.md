# Util/Cron/TCronModule

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TCronModule`**

## Class Info
**Location:** `framework/Util/Cron/TCronModule.php`
**Namespace:** `Prado\Util\Cron`

## Overview
Cron-style scheduled task engine. `TCronModule` manages task registration and execution timing. Tasks run when `prado-cli cron` is invoked by the system crontab (typically every minute). The module tracks last-run times so it won't run a task more frequently than its schedule allows.

## Architecture

```
system crontab (every minute)
    → prado-cli cron
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
# Run all pending tasks:
php prado-cli.php /path/to/app cron

# List tasks and their schedules:
php prado-cli.php /path/to/app cron list

# Manage DB cron tasks (TDbCronManager):
php prado-cli.php /path/to/app db-cron tasks
php prado-cli.php /path/to/app db-cron run <taskname>
php prado-cli.php /path/to/app db-cron add <name> <task-id> <schedule>
php prado-cli.php /path/to/app db-cron remove <name>
```

## Patterns & Gotchas

- **System crontab is required** — `TCronModule` itself doesn't schedule anything; the OS crontab must call `prado-cli cron` periodically.
- **Last-run tracking** — stored in application global state. If global state is cleared, tasks may run again immediately.
- **User context** — tasks can run as a specific user via `UserName` property (requires `TUserManager` to be configured). The user is the task fiber's user in `TFiberContext`, so a task that calls `$app->setUser()` changes only its own fiber.
- **A task future never rejects** — `runTask()` catches the task's exception; a rejection of the `TAsync::run()` future means `logCronTaskError()` itself threw.
- **Deadlines are cooperative** — a task that blocks in PHP code is not interrupted by its `Timeout`; only a wait that takes the token ends.
- **`fxGetCronTaskInfos`** — the standard hook for modules to self-register maintenance tasks. Always prefer this over manual task registration.
