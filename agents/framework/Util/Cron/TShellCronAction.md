# Util/Cron/TShellCronAction

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TShellCronAction`**

## Class Info
**Location:** `framework/Util/Cron/TShellCronAction.php`
**Namespace:** `Prado\Util\Cron`

## Overview
TShellCronAction provides CLI commands for managing TCronModule from the command line. It implements the `cron` action with sub-commands: `run` (execute pending tasks), `tasks` (show configured tasks), and `index` (show registered task information).

## Key Properties/Methods

- `getModuleClass()` - Returns TCronModule::class
- `getCronModule()` / `setCronModule($cron)` - Gets or sets the cron module instance
- `actionRun($args)` - Runs all pending cron tasks
- `actionTasks($args)` - Displays configured tasks with schedule, last run, next run, and run count
- `actionIndex($args)` - Displays registered task information from the application

## See Also

- [TCronModule](../TCronModule.md)
- [TShellDbCronAction](TShellDbCronAction.md)
