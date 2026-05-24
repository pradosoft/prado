# Util/Cron/TDbCronCleanLogTask

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TDbCronCleanLogTask`**

## Class Info
**Location:** `framework/Util/Cron/TDbCronCleanLogTask.php`
**Namespace:** `Prado\Util\Cron`
**Extends:** [`TCronTask`](TCronTask.md)

## Overview
`TDbCronCleanLogTask` automatically purges old cron execution log entries from the [`TDbCronManager`](TDbCronManager.md) database table. Entries older than `TimePeriod` seconds are deleted. The default period is 28 days (2,419,200 seconds).

[`TDbCronManager`](TDbCronManager.md) auto-registers this task via `fxGetCronTaskInfos` under the name `'cronclean'` on the module that owns the cron table.

## Key Properties/Methods

- `getTimePeriod()` / `setTimePeriod(int $timeperiod)` — seconds before which log entries are deleted (default: `2419200` = 28 days)
- `execute(TCronModule|TDbCronManager $cron)` — clears the log on the `TDbCronManager` specified by `ModuleId` (or the running cron module if none set); writes cleared-count to shell output when run via CLI

## Example Configuration

```xml
<job Name="cronclean" Schedule="0 2 * * *"
     Task="Prado\Util\Cron\TDbCronCleanLogTask"
     ModuleId="cron" TimePeriod="604800" />
```

(604800 = 7 days)

## See Also

- [`TCronTask`](TCronTask.md)
- [`TDbCronManager`](TDbCronManager.md)
