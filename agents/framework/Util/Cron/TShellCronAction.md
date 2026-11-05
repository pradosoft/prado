# Util/Cron/TShellCronAction

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TShellCronAction`**

## Class Info
**Location:** `framework/Util/Cron/TShellCronAction.php`
**Namespace:** `Prado\Util\Cron`
**Extends:** `TShellAction`

## Overview
`TShellCronAction` provides the `cron` CLI action for running and inspecting [`TCronModule`](TCronModule.md) from the command line. Sub-commands: `run` (execute pending tasks), `tasks` (list configured tasks with schedule/last-run/next-run info), and `index` (list all registered task infos from `fxGetCronTaskInfos`).

[`TDbCronManager`](TDbCronManager.md) replaces the shell class with [`TShellDbCronAction`](TShellDbCronAction.md) in its constructor, which extends this class with add/update/remove commands.

## CLI Commands

```bash
php prado-cli.php /app cron          # run pending cron tasks
php prado-cli.php /app cron tasks    # display configured tasks (schedule, last run, next run, count)
php prado-cli.php /app cron index    # display registered task info from fxGetCronTaskInfos
```

## Key Methods

- `getModuleClass()` — returns `TCronModule::class`; override in subclasses to target a different module type
- `getCronModule()` — finds and returns the first `TCronModule` instance from the application modules
- `actionRun($args)` — calls `processPendingTasks()` on the cron module
- `actionTasks($args)` — displays task table: name, schedule, last run, next run, run count
- `actionIndex($args)` — displays task info from `fxGetCronTaskInfos`

## See Also

- [`TCronModule`](TCronModule.md)
- [`TShellDbCronAction`](TShellDbCronAction.md) — extends this for `TDbCronManager`
