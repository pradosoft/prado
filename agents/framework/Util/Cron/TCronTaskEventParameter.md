# Util/Cron/TCronTaskEventParameter

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TCronTaskEventParameter`**

## Class Info
**Location:** `framework/Util/Cron/TCronTaskEventParameter.php`
**Namespace:** `Prado\Util\Cron`
**Extends:** `TEventParameter`
**Since:** 4.4.0

## Overview
The parameter of the [`TCronModule`](TCronModule.md#task-events-and-pings-440) task events `onTaskStart`, `onTaskEnd`, `onTaskFailure`, `onTaskRetry`, `onTaskOverdue`, `onTaskSkipped`, and `onTaskPing`, and their global mirrors `fxCronTaskStart`, `fxCronTaskEnd`, `fxCronTaskFailure`, `fxCronTaskRetry`, `fxCronTaskOverdue`, `fxCronTaskSkipped`, and `fxCronTaskPing`.

| Property | Set for | Meaning |
|---|---|---|
| `Task` | all | the `TCronTask` |
| `Manual` | all | the run was asked for (`cron/now`) |
| `Cancel` | start | a handler sets it to skip the run |
| `Reason` | skipped | `REASON_DENIED`, `REASON_PAUSED`, `REASON_DEADLINE`, `REASON_WINDOW`, `REASON_CONDITION`, `REASON_RUNNING`, `REASON_CANCELLED`, or `REASON_CLAIMED` |
| `Result` | end, failure, the pings after a run | a `TCronModule::RESULT_*` value |
| `Duration` | end, failure, overdue, the pings after a run | the seconds the run took, or so far |
| `Exception` | end, failure, retry | the exception a task in the cron process threw; null for a Process task's child |
| `Attempt` | end, failure, retry | the attempt, from 1 |
| `Ping` | ping | `TCronTask::PING_BEFORE`, `PING_AFTER`, `PING_SUCCESS`, or `PING_FAILURE` |
| `Url` | ping | the URL to ping |
| `Handled` | ping | a handler sets it when it delivered the ping, so the module does not send it |

The setters return the parameter, so they chain.

## See Also

- [TCronModule](TCronModule.md)
- [TCronSweepEventParameter](TCronSweepEventParameter.md)
- [TCronDaemonEventParameter](TCronDaemonEventParameter.md)
