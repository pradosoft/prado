# Util/Cron/TCompoundCronTask

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TCompoundCronTask`**

## Class Info
**Location:** `framework/Util/Cron/TCompoundCronTask.php`
**Namespace:** `Prado\Util\Cron`
**Extends:** [`TCronTask`](TCronTask.md)
**Since:** 4.4.0

## Overview
Runs several steps as one run: one claim, one result, one set of task events and pings. Each step is a task as a job's `Task` is (a class name or `module->method(args)`, through `TCronModule::instanceTask()`), or `@name`, the `execute()` of a configured task.

```xml
<job name="nightly" schedule="0 3 * * *" task="Prado\Util\Cron\TCompoundCronTask"
     Tasks="dbcache->flushCacheExpired(true); MyApp\ReportTask; @cleanup" />
```

## Properties

| Property | Default | Meaning |
|---|---|---|
| `Tasks` | `[]` | The steps, a `;` list (`STEP_SEPARATOR`) or an array; none throws `compoundcrontask_no_tasks`. |
| `StopOnFailure` | true | A step that throws stops the rest; false runs the rest, and the first exception ends the run. |
| `Parallel` | false | The steps run at once in fibers of their own (`TAsync::run()`); the first exception ends the run after all finish. |

A created step is named `<compound>#<n>` and takes the compound's `UserName`; every step runs with the compound's `CancellationToken` (`executeStep()`). An `@name` step runs only the task's work: its schedule, claim, and events are the compound's, and an unknown name throws `compoundcrontask_unknown_task`. Before any step runs, `checkReferences()` follows the `@name` steps through nested compounds; a step that leads back to a compound on the path throws `compoundcrontask_cycle`, and a compound referenced twice without a loop runs twice. Chains (`Then`) run whole tasks after a run instead; see [TCronModule](TCronModule.md#chains-priority-and-monitoring-440).

## See Also

- [TCronTask](TCronTask.md)
- [TCronModule](TCronModule.md)
