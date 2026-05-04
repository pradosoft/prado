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
            → [TCronModule](../TCronModule.md)::processPendingTasks()
                → evaluates each task's [TTimeScheduler](TTimeScheduler.md)
                → runs due tasks
```

## TCronModule

Register in `application.xml`:

```xml
<module id="cron" class="Prado\Util\Cron\TCronModule">
    <task name="cleanup" class="MyCleanupTask" Schedule="0 2 * * *" />
    <task name="report"  class="Prado\Util\Cron\TCronMethodTask"
          ModuleClass="reporting" MethodName="sendDailyReport"
          Schedule="30 8 * * 1-5" />
</module>
```

### Key Methods

```php
$cron = $app->getModule('cron');
$cron->addTask($task);                    // register a TCronTask dynamically
$cron->processPendingTasks();             // check and run due tasks
$cron->getTaskInfos();                    // array of TCronTaskInfo
```

### fxGetCronTaskInfos

`[TCronModule](../TCronModule.md)` fires global event `fxGetCronTaskInfos` during init. Modules can respond with an array of `[TCronTaskInfo](TCronTaskInfo.md)` to auto-register their own maintenance tasks (e.g., `TDbCache` registers a cleanup task this way).

```php
public function fxGetCronTaskInfos($sender, $param): array
{
    return [new TCronTaskInfo('cleanup', 'MyCleanupTask', '0 2 * * *')];
}
```

## TDbCronModule

Extends `TCronModule` with database storage for tasks and execution logs.

```xml
<module id="cron" class="Prado\Util\Cron\TDbCronModule"
        ConnectionID="db" />
```

Additional features:
- Dynamic task registration (tasks persisted to DB)
- Execution logging with start/end times and output
- Failed-task retry logic
- `[TDbCronCleanLogTask](TDbCronCleanLogTask.md)` — pre-built task to purge old log entries
- CLI: `prado-cli db-cron` for managing DB-backed tasks

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

# Manage DB cron tasks:
php prado-cli.php /path/to/app db-cron list
php prado-cli.php /path/to/app db-cron run <taskname>
```

## Patterns & Gotchas

- **System crontab is required** — `TCronModule` itself doesn't schedule anything; the OS crontab must call `prado-cli cron` periodically.
- **Last-run tracking** — stored in application global state. If global state is cleared, tasks may run again immediately.
- **User context** — tasks can run as a specific user via `UserName` property (requires `TUserManager` to be configured).
- **`fxGetCronTaskInfos`** — the standard hook for modules to self-register maintenance tasks. Always prefer this over manual task registration.
