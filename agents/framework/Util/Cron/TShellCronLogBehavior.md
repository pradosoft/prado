# Util/Cron/TShellCronLogBehavior

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TShellCronLogBehavior`**

## Class Info
**Location:** `framework/Util/Cron/TShellCronLogBehavior.php`
**Namespace:** `Prado\Util\Cron`

## Overview
TShellCronLogBehavior is a behavior that enables cron logging to the shell. It wraps TShellWriter and provides dynamic event handlers for logging cron execution, including task start, task end, and flush operations.

## Key Properties/Methods

- `getOutputWriter()` / `setOutputWriter($writer)` - Gets or sets the output writer
- `dyWrite($str, $p1, $p2)` - Writes with attributes to the output writer
- `dyWriteLine($str, $p1, $p2)` - Writes a line with attributes to the output writer
- `dyFlush($callchain)` - Flushes the output writer buffer
- `dyLogCron($numtasks, $callchain)` - Logs when cron starts running
- `dyLogCronTask($task, $username, $callchain)` - Logs when a task starts
- `dyLogCronTaskEnd($task, $callchain)` - Logs when a task ends

## See Also

- [TShellCronAction](TShellCronAction.md)
- [TCronModule](../TCronModule.md)
