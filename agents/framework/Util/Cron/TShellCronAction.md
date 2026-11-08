# Util/Cron/TShellCronAction

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TShellCronAction`**

## Class Info
**Location:** `framework/Util/Cron/TShellCronAction.php`
**Namespace:** `Prado\Util\Cron`
**Extends:** `TShellAction`

## Overview
`TShellCronAction` provides the `cron` CLI action for running and inspecting [`TCronModule`](TCronModule.md) from the command line. Sub-commands: `tasks` (the default action: list configured tasks with schedule/last-run/next-run info, filtered by task name and `@user`), `run` (execute pending tasks; the system crontab entry), `index` (list all registered task infos from `fxGetCronTaskInfos`), and the daemon commands `daemon` (state), `retire` (hand the lease to the next tick with no gap), and `stop` (end at once).

When the application user has the `TUserPermissionsBehavior`, the constructor removes `run`, `retire`, and `stop` without `cron_run_pending` (`removeShellMethod()`), and `tasks` is limited to the user's own tasks without `cron_read_tasks` (4.4.0).

[`TDbCronManager`](TDbCronManager.md) replaces the shell class with [`TShellDbCronAction`](TShellDbCronAction.md) in its constructor, which extends this class with add/update/remove/log/flush commands.

## CLI Commands

```bash
php prado-cli.php /app cron [name] [@user]    # display configured tasks (schedule, last run, next run, count); the default action (4.4.0)
php prado-cli.php /app cron/run               # run pending cron tasks; the system crontab entry
php prado-cli.php /app cron/index             # display registered task info from fxGetCronTaskInfos
php prado-cli.php /app cron/daemon            # running, retiring, or no daemon (4.4.0)
php prado-cli.php /app cron/retire [seconds]  # retire the daemon: the next cron/run tick takes over with no gap (4.4.0)
php prado-cli.php /app cron/stop [seconds]    # stop the daemon at once; the next cron/run tick starts a new one (4.4.0)
```

The optional seconds are how long the command waits for the daemon's acknowledgment (default 10).

Name and user filters are patterns: `_` is a wild card and `%` a wild string. A one-off `@<timestamp>` schedule is displayed as `@(date)`.

## Key Methods

- `getModuleClass()` — returns `TCronModule::class`; override in subclasses to target a different module type
- `getCronModule()` — finds and returns the first `TCronModule` instance from the application modules
- `actionRun($args)` — calls `processPendingTasks()` on the cron module; with `Daemon` this elects or defers to the cron daemon (see [TCronModule](TCronModule.md#daemon-mode-440))
- `actionTasks($args)` — displays task table: name, schedule, last run, next run, user, run count; through `getTasks()` or `getUserTasks()`
- `actionIndex($args)` — displays task info from `fxGetCronTaskInfos`
- `actionDaemon($args)`, `actionRetire($args)`, `actionStop($args)` — `getDaemonState()`, `requestDaemonRetire()`, `requestDaemonStop()` on the module (4.4.0)
- `time()` — protected clock seam for the displayed dates (4.4.0)

## See Also

- [`TCronModule`](TCronModule.md)
- [`TShellDbCronAction`](TShellDbCronAction.md) — extends this for `TDbCronManager`
