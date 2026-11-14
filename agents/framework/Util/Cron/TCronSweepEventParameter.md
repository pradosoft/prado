# Util/Cron/TCronSweepEventParameter

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TCronSweepEventParameter`**

## Class Info
**Location:** `framework/Util/Cron/TCronSweepEventParameter.php`
**Namespace:** `Prado\Util\Cron`
**Extends:** `TEventParameter`
**Since:** 4.4.0

## Overview
The parameter of the [`TCronModule`](TCronModule.md) sweep events `onSweepStart` and `onSweepEnd` (and their global mirrors `fxCronSweepStart`/`fxCronSweepEnd`). A sweep is one pass of `processPendingTasks()`, per-minute or inside the daemon: the pending tasks are collected, then run.

| Property | Set for | Meaning |
|---|---|---|
| `Tasks` | start, end | the pending `TCronTask` objects by name. A start handler replaces the set with `setTasks()` to remove, add, or reorder tasks, or edits a task object in place; a `setSchedule()` persists through `updateTaskInfo()`. A removed task stays pending for the next sweep. |
| `Daemon` | start, end | whether the sweep runs inside the cron daemon |
| `Results` | end | whether each task ran without an exception, by name |
| `Count` | start, end | the number of tasks |
| `Failed` | end | the number of tasks that threw |

```php
// A module that must not let a task run while it reindexes:
public function fxCronSweepStart($cron, TCronSweepEventParameter $param)
{
    if ($this->isReindexing()) {
        $tasks = $param->getTasks();
        unset($tasks['searchindex']);
        $param->setTasks($tasks);
    }
}
```

In the per-minute second-resolution loop the start parameter carries the tasks due through the minute; the loop re-polls after each wait but runs only the tasks the handler left in.

## See Also

- [TCronModule](TCronModule.md)
- [TCronDaemonEventParameter](TCronDaemonEventParameter.md)
