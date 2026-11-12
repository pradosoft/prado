# Util/Cron/TShellDbCronAction

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TShellDbCronAction`**

## Class Info
**Location:** `framework/Util/Cron/TShellDbCronAction.php`
**Namespace:** `Prado\Util\Cron`
**Extends:** [`TShellCronAction`](TShellCronAction.md)

## Overview
`TShellDbCronAction` extends [`TShellCronAction`](TShellCronAction.md) to add database-backed cron management commands. In addition to `run`, `tasks`, and `index`, it provides `add`, `update`, and `remove` for the tasks in the [`TDbCronManager`](TDbCronManager.md) database, `log` and `flush` for the execution log, and `migrate` for the table (4.4.0).

It is automatically used as the shell action class when `TDbCronManager` is configured (it sets `$_shellClass` to `TShellDbCronAction` in the constructor).

When the application user has the `TUserPermissionsBehavior`, the constructor removes `run` without `cron_run_pending`, and `add` and `migrate` without `cron_add_task`/`cron_add_task_global`; `tasks`, `log`, and `flush` are limited to the user's own rows without `cron_read_tasks`, `cron_log_read`, or `cron_log_delete`.

## CLI Commands

```bash
php prado-cli.php /app cron [name] [@user]            # display tasks (config + DB), filtered by task name and/or @user; the default action (4.4.0)
php prado-cli.php /app cron/run                       # run pending DB cron tasks; the system crontab entry
php prado-cli.php /app cron/index                     # display registered task info
php prado-cli.php /app cron/add <name> <task-id> <schedule> [properties...]
php prado-cli.php /app cron/update <name> [properties...]
php prado-cli.php /app cron/remove <name>
php prado-cli.php /app cron/log [name] [@user]        # display the execution log, filtered
php prado-cli.php /app cron/flush [period] [name] [@user]   # delete log rows older than period, filtered
php prado-cli.php /app cron/migrate                   # create the table when missing; add the 4.4.0 columns to an older one
php prado-cli.php /app cron/daemon | cron/retire [seconds] | cron/stop [seconds]   # the daemon commands of TShellCronAction
php prado-cli.php /app cron/top [seconds] [count] | cron/exec <name>               # the live view and the Process task child of TShellCronAction
```

Name and user filters are `LIKE` patterns: `_` is a wild card and `%` a wild string. The `flush` period is seconds or a `\DateInterval` specification (`P7D`, `"1 week"`); without a period every matching log row is deleted.

## Key Methods

- `getModuleClass()` — returns `TDbCronManager::class` (overrides parent which returns `TCronModule::class`)
- `actionAdd($args)` — adds a task to the database with name, task ID, schedule, and optional properties
- `actionUpdate($args)` — updates a task's schedule, username, moduleid, and other properties
- `actionRemove($args)` — removes a task from the database by name
- `actionLog($args)` — displays the cron log through `getCronLogCount()`/`getCronLog()` (4.4.0)
- `actionFlush($args)` — deletes log rows through `clearCronLog()` (4.4.0)
- `actionMigrate($args)` — brings the table up to date through `migrateTable()` and reports what it did (4.4.0)

## See Also

- [`TShellCronAction`](TShellCronAction.md)
- [`TDbCronManager`](TDbCronManager.md)
