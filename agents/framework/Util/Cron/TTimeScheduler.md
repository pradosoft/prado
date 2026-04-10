# Util/Cron/TTimeScheduler

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TTimeScheduler`**

## Class Info
**Location:** `framework/Util/Cron/TTimeScheduler.php`
**Namespace:** `Prado\Util\Cron`

## Overview
TTimeScheduler is a cron expression parser that calculates the next trigger time for a schedule. It supports standard Linux cron format (minute, hour, day, month, dayOfWeek, year) plus special expressions like `@daily`, `@hourly`, `@yearly`, and Unix timestamp shortcuts. Supports 8 languages for month/day names.

## Key Properties/Methods

- `getSchedule()` / `setSchedule($schedule)` - Gets/sets the cron schedule expression
- `getNextTriggerTime($priortime)` - Calculates next Unix timestamp when task should trigger
- `days_in_month($month, $year)` - Returns number of days in a month (accounts for leap years)

## Schedule Format

```
minute hour day month dayOfWeek year
  *     *    *    *       *      *
```

- Fields: minute (0-59), hour (0-23), day (1-31), month (1-12), dayOfWeek (0-6), year (1970-2099)
- Special shortcuts: `@yearly`, `@annually`, `@monthly`, `@weekly`, `@daily`, `@hourly`, `@midnight`
- Unix timestamp: `@<timestamp>` for one-off tasks
- Supports ranges (`1-5`), steps (`*/5`), lists (`1,3,5`), closest weekday (`15W`), last day (`L`), week number (`3#2`)

## See Also

- [TCronTask](TCronTask.md)
- [TCronModule](../TCronModule.md)
