# Util/Cron/TDbCronCleanLogTask

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TDbCronCleanLogTask`**

## Class Info
**Location:** `framework/Util/Cron/TDbCronCleanLogTask.php`
**Namespace:** `Prado\Util\Cron`

## Overview
TDbCronCleanLogTask is a cron task that automatically purges old cron log entries from the database. It extends TCronTask and cleans logs older than a configurable time period (default: 28 days).

## Key Properties/Methods

- `getTimePeriod()` / `setTimePeriod($timeperiod)` - Time period in seconds before which logs are deleted (default: 2419200 = 28 days)
- `execute($cron)` - Clears the log of the specified TDbCronModule, outputs count of cleared entries

## See Also

- [TCronTask](TCronTask.md)
- [TDbCronModule](TDbCronModule.md)
