# TCronTask

### Directories

[Util](../) > [Cron](Cron/) > TCronTask

**Location:** `framework/Util/Cron/TCronTask.php`
**Namespace:** `Prado\Util\Cron`

## Overview

TCronTask is the abstract base class for all cron tasks. Subclasses must implement the `execute()` method to perform the actual task logic. It extends TApplicationComponent and provides scheduling, timing, and persistence capabilities for tasks.

## Key Properties/Methods

- `getName()` / `setName($name)` - The unique name of the task
- `getSchedule()` / `setSchedule($schedule)` - The cron-style schedule expression
- `getUserName()` / `setUserName($username)` - The user ID executing the task
- `getModuleId()` / `setModuleId($moduleId)` - The utility module for the task
- `getModule()` - Gets the module instance by module ID
- `getProcessCount()` / `setProcessCount($count)` - Number of times the task has run
- `getLastExecTime()` / `setLastExecTime($v)` - Last execution timestamp
- `getNextTriggerTime()` - Calculates next trigger time based on schedule
- `getIsPending()` - Whether current time is after the next trigger time
- `getScheduler()` - Gets the TTimeScheduler instance
- `resetTaskLastExecTime()` - Resets lastExecTime to prevent false triggers
- `execute($cronModule)` - Abstract method; implement to run task logic

## See Also

- [TCronModule](../TCronModule.md)
- [TTimeScheduler](TTimeScheduler.md)
