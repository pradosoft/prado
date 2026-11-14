# Util/Cron/TShellCronAction

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TShellCronAction`**

## Class Info
**Location:** `framework/Util/Cron/TShellCronAction.php`
**Namespace:** `Prado\Util\Cron`
**Extends:** `TShellAction`

## Overview
`TShellCronAction` provides the `cron` CLI action for running and inspecting [`TCronModule`](TCronModule.md) from the command line. Sub-commands: `tasks` (the default action: list configured tasks with schedule, mode, last run and result, next run, running or paused state, and description, filtered by task name and `@user`), `now` (run a task at once), `next` (the next trigger times), `pause` and `resume`, `run` (execute pending tasks; the system crontab entry), `top` (the live view of the cron processes), `exec` (run one task in this process: the child of a Process task), `index` (list all registered task infos from `fxGetCronTaskInfos`), and the daemon commands `daemon` (state), `retire` (hand the lease to the next tick with no gap), and `stop` (end at once).

When the application user has the `TUserPermissionsBehavior`, the constructor removes `run`, `exec`, `now`, `pause`, `resume`, `retire`, and `stop` without `cron_run_pending` (`removeShellMethod()`), and `tasks` is limited to the user's own tasks without `cron_read_tasks` (4.4.0).

[`TDbCronManager`](TDbCronManager.md) replaces the shell class with [`TShellDbCronAction`](TShellDbCronAction.md) in its constructor, which extends this class with add/update/remove/log/flush commands.

## CLI Commands

```bash
php prado-cli.php /app cron [name] [@user] [#tag]   # display configured tasks (schedule, last run, next run, count); the default action (4.4.0)
php prado-cli.php /app cron/run               # run pending cron tasks; the system crontab entry
php prado-cli.php /app cron/top [seconds] [count] [#tag]   # live view of the cron processes, running tasks, next triggers, last results with success rate and average seconds (4.4.0)
php prado-cli.php /app --quiet=1 cron/report [json|prometheus] [#tag]   # the monitor report for scripts (4.4.0)
php prado-cli.php /app cron/exec <name>       # run one task in this process; the child of a Process task (4.4.0)
php prado-cli.php /app cron/now <name>        # run a task now, whatever its schedule, and show its result (4.4.0)
php prado-cli.php /app cron/next [name|#tag] [count]   # the next trigger times, in each task's time zone (4.4.0)
php prado-cli.php /app cron/pause <name|#tag>   # its triggers, or the triggers of the tag's tasks, pass without runs until resumed (4.4.0)
php prado-cli.php /app cron/resume <name|#tag>  # resume a paused task, or the tag's tasks (4.4.0)
php prado-cli.php /app cron/index             # display registered task info from fxGetCronTaskInfos
php prado-cli.php /app cron/daemon            # running, retiring, or no daemon (4.4.0)
php prado-cli.php /app cron/retire [seconds]  # retire the daemon: the next cron/run tick takes over with no gap (4.4.0)
php prado-cli.php /app cron/stop [seconds]    # stop the daemon at once; the next cron/run tick starts a new one (4.4.0)
```

The optional seconds are how long the command waits for the daemon's acknowledgment (default 10).

Name and user filters are patterns: `_` is a wild card and `%` a wild string. A one-off `@<timestamp>` schedule is displayed as `@(date)`.

## Key Methods

- `getModuleClass()` — returns `TCronModule::class`; override in subclasses to target a different module type
- `getCronModule()` — returns the module set by `setCronModule()`, else the first `TCronModule` of the application modules; without one it writes an error and returns null, and every command then returns at once
- `actionRun($args)` — turns on the module's `EnableStatus` when it is unset, then calls `processPendingTasks()`; with `Daemon` this elects or defers to the cron daemon (see [TCronModule](TCronModule.md#daemon-mode-440))
- `actionTasks($args)` — displays task table: name, schedule, task, mode, last run, last result and seconds (`getTaskResults()`), next run, user, run count, and state (`running`, or `pid <id>` for a Process task's child, from `getStatuses()`); through `getTasks()` or `getUserTasks()`
- `actionReport($args)` — prints `getMonitorReport()` of the readable tasks as pretty JSON, or `getMonitorMetrics()` with `prometheus`; another format is an error (4.4.0)
- `actionTop($args)` — `renderTop()` once, or in a terminal (`isInteractive()`) every `[seconds]` (default 1) after clearing the screen, until interrupted or `[count]` views (4.4.0). A view renders `getMonitorReport()` of the tasks the user may read (the same report as `cron/report` and `TCronMonitorResponse`): a line per live or ended cron process (role, pid, host, uptime; for the daemon its state, wake transport, next sweep, and sweep and task counts), the concurrency, then tables of the runs in progress, the next triggers, and the last results, at most `TOP_ROWS` (10) each. `flushView()` and `sleep()` are the test seams.
- `actionNow($args)` — `runTaskNow($task)` (not skipped by pause, deadline, window, or condition; run lock, claim, events, and pings apply; turns on an unset `EnableStatus` so `cron/top` shows it), then the result from `onTaskEnd` or the reason from `onTaskSkipped` (4.4.0)
- `actionPause($args)`, `actionResume($args)` — `pauseTask()`/`resumeTask()` on a task the user may read, or with `#tag` on each such task with the tag (4.4.0)
- `extractTag(&$args)` — takes a `#tag` argument out of the arguments; `cron/tasks`, `cron/top`, and `cron/report` filter by it, and `cron/next` takes it in place of the name (4.4.0)
- `actionNext($args)` — the next trigger times of the readable tasks, soonest first, one per task or `count` (default 5 with a name filter), each in the task's `TimeZone`; `getNextTriggerTimes()` is the pending or next trigger followed by the scheduler's times after it or after now (4.4.0)
- `actionExec($args)` — `executeTask()` on the named task without claiming it; throws `TExitException` with `TCronModule::EXIT_ERROR`, `EXIT_NO_TASK`, or `EXIT_DENIED` (4.4.0)
- `actionIndex($args)` — displays task info from `fxGetCronTaskInfos`
- `actionDaemon($args)`, `actionRetire($args)`, `actionStop($args)` — `getDaemonState()`, `requestDaemonRetire()`, `requestDaemonStop()` on the module (4.4.0)
- `time()` — protected clock seam for the displayed dates (4.4.0)

## See Also

- [`TCronModule`](TCronModule.md)
- [`TShellDbCronAction`](TShellDbCronAction.md) — extends this for `TDbCronManager`
