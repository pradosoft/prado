# Util/Cron/TShellDbCronAction

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TShellDbCronAction`**

## Class Info
**Location:** `framework/Util/Cron/TShellDbCronAction.php`
**Namespace:** `Prado\Util\Cron`
**Extends:** [`TShellCronAction`](TShellCronAction.md)

## Overview
`TShellDbCronAction` extends [`TShellCronAction`](TShellCronAction.md) to add database-backed cron management commands. In addition to `run`, `tasks`, and `index`, it provides `add`, `update`, and `remove` commands for managing tasks in the [`TDbCronManager`](TDbCronManager.md) database.

It is automatically used as the shell action class when `TDbCronManager` is configured (it sets `$_shellClass` to `TShellDbCronAction` in the constructor).

## CLI Commands

```bash
php prado-cli.php /app db-cron              # list overview
php prado-cli.php /app db-cron run          # run pending DB cron tasks
php prado-cli.php /app db-cron tasks        # display configured tasks (config + DB)
php prado-cli.php /app db-cron index        # display registered task info
php prado-cli.php /app db-cron add <name> <task-id> <schedule> [properties...]
php prado-cli.php /app db-cron update <name> [properties...]
php prado-cli.php /app db-cron remove <name>
```

## Key Methods

- `getModuleClass()` — returns `TDbCronManager::class` (overrides parent which returns `TCronModule::class`)
- `actionAdd($args)` — adds a task to the database with name, task ID, schedule, and optional properties
- `actionUpdate($args)` — updates a task's schedule, username, moduleid, and other properties
- `actionRemove($args)` — removes a task from the database by name

## See Also

- [`TShellCronAction`](TShellCronAction.md)
- [`TDbCronManager`](TDbCronManager.md)
