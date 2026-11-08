# Util/Cron/TDbCronManager

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TDbCronManager`**

## Class Info
**Location:** `framework/Util/Cron/TDbCronManager.php`
**Namespace:** `Prado\Util\Cron`
**Extends:** [`TCronModule`](TCronModule.md)
**Implements:** [`IDbModule`](../IDbModule.md), `IPermissions`
**Since:** 4.3.3

> **Note:** `TDbCronModule` (since 4.2.0) was a deprecated alias that extended `TDbCronManager`; it is removed in 4.4.0. Use `TDbCronManager`.

## Overview
`TDbCronManager` does everything [`TCronModule`](TCronModule.md) does, but stores all tasks and execution logs in a database table (`crontabs` by default). It adds:

- Dynamic task CRUD (`addTask`, `updateTask`, `removeTask`) and filtered, paged reads (`getTasks`, `getUserTasks`, counts)
- Execution logging to the DB (`LogCronTasks`), with name and user filters on the log reads and flush
- Runtime task queue: tasks that run at `onEndRequest`, and `deferTask()` for a closure with a cron fallback (4.4.0)
- A per-row next execution time (`nextexectime`), so `getPendingTasks()` is one query, the cron shell can poll within the minute (`PollsPerMinute`), and a run is claimed against the row so one of several hosts runs each trigger (4.4.0)
- Permission-gated CLI management via [`TShellDbCronAction`](TShellDbCronAction.md)
- Auto-creates the DB table if needed, and adds the `nextexectime` column to a table created before 4.4.0

## Configuration

```xml
<modules>
  <module id="db" class="Prado\Data\TDataSourceConfig">
    <database ConnectionString="mysql:host=localhost;dbname=mydb"
      Username="dbuser" Password="dbpass" />
  </module>
  <module id="cron" class="Prado\Util\Cron\TDbCronManager"
      ConnectionID="db" DefaultUserName="cron"
      EnableRequestCron="false" LogCronTasks="true">
    <job Name="dbcacheclean" Schedule="0 0 * * *" Task="dbcache->flushCacheExpired(true)" />
  </module>
</modules>
```

PHP style:

```php
'cron' => [
    'class' => 'Prado\Util\Cron\TDbCronManager',
    'properties' => [
        'ConnectionID' => 'db',
        'LogCronTasks' => 'true',
    ],
    'jobs' => [
        ['name' => 'dbcacheclean', 'schedule' => '0 0 * * *', 'task' => 'dbcache->flushCacheExpired(true)'],
    ],
],
```

## DB Table Schema

Auto-created as `crontabs` (configurable via `TableName`):

| Column | Type | Notes |
|--------|------|-------|
| `tabuid` | INTEGER PK AUTOINCREMENT | Row ID |
| `name` | VARCHAR(127) | Task name (unique for active tasks) |
| `schedule` | VARCHAR(127) | Cron expression |
| `task` | VARCHAR(256) | Class or `module->method(args)` |
| `moduleid` | VARCHAR(127) | Optional module context |
| `username` | VARCHAR(127) | Run-as user; `NULL` is the `DefaultUserName` |
| `options` | MEDIUMTEXT | Serialized `TCronTask` object |
| `processcount` | INT | Times task has run |
| `lastexectime` | VARCHAR(20) | Unix timestamp of last run |
| `nextexectime` | INT | Unix timestamp of the next trigger after `lastexectime` (4.4.0); `getPendingTasks()` selects `nextexectime <= now` |
| `active` | BOOLEAN | `1` = DB task, `0` = config task shadow, `NULL` = log entry |

Active rows (`active IS NOT NULL`) are task definitions; rows with `active IS NULL` are execution log entries. `ensureTable()` adds the `nextexectime` column, its index, and the values for the active rows to a table created before 4.4.0. `migrateTable()` does the same on demand, creates a missing table whatever `AutoCreateCronTable` says, and returns what it did; `cron/migrate` calls it at deployment. The indexes are named `<table>_tname`, `<table>_tclass`, `<table>_tnext`, `<table>_tuser`, and `<table>_tactive`, so several cron tables share a SQLite or PostgreSQL schema (4.4.0; earlier tables keep `tname`, `tclass`, `tactive`).

## Key Properties

| Property | Default | Description |
|----------|---------|-------------|
| `ConnectionID` | — | ID of `TDataSourceConfig` module; frozen after `init()` |
| `TableName` | `'crontabs'` | DB table for tasks and logs; an SQL identifier (4.4.0); frozen after `init()` |
| `AutoCreateCronTable` | `true` | Create table automatically if absent; frozen after `init()` |
| `LogCronTasks` | `true` | Insert a log row per task execution |
| `PollsPerMinute` | `1` | Times per minute the cron shell polls for pending tasks, clamped to 1..60 (4.4.0); frozen after `init()`. Above 1, `processPendingTasks()` in a crontab shell runs the tasks due within the minute at their trigger second through `runPendingTasksWithWake()`. |
| `SecondResolution` | `false` | Alias: `true` sets `PollsPerMinute` to 60, `false` to 1 (4.4.0) |

## Task CRUD

```php
$cron->addTask(TCronTask $task, bool $runtime = false): ?bool
$cron->updateTask(TCronTask $task): ?bool
$cron->removeTask(string|TCronTask $untask): ?bool
$cron->getTask(string $name, bool $checkExisting = true, bool $asObject = true): null|array|TCronTask
$cron->getTasks(?string $name = null, int $pageSize = 0, int $offset = 0, ?bool $sortingAsc = true): ?TCronTask[]   // DB tasks + config tasks
$cron->getTasksCount(?string $name = null): ?int
$cron->getUserTasks(string $username, ?string $name = null, int $pageSize = 0, int $offset = 0, ?bool $sortingAsc = true): ?TCronTask[]
$cron->getUserTasksCount(string $username, ?string $name = null): ?int
$cron->taskExists(string $name): ?bool
```

Name and user filters are SQL `LIKE` patterns: `_` is a wild card and `%` a wild string. A user filter that matches `DefaultUserName` also selects the tasks without a user. A permission denial returns `null` (and `dyLogPermissionFailed` is raised); a plain miss returns `false`/`[]`.

`NAME_VALIDATOR_REGEX` enforces that names contain no whitespace, backticks, quotes, `<>`, `%`, and do not start with `*`.

`addTask()`/`updateTask()` call `signalWakeIfEarliest()`: when the task is due within the current minute and is the earliest pending task, `signalWake()` kicks a sleeping second-resolution cron process (see [INDEX](INDEX.md#second-resolution-wake)).

## Log Management

```php
$cron->getCronLog(?string $name = null, int $pageSize = 0, int $offset = 0, ?bool $sortingDesc = null, ?string $username = null): ?array
$cron->getCronLogCount(?string $name = null, ?string $username = null): ?int
$cron->clearCronLog(null|\DateInterval|int|string $period = null, ?string $name = null, ?string $username = null): ?int
$cron->removeCronLogItem(int $taskUID): ?bool
```

`$period` is seconds, a `\DateInterval`, or a `\DateInterval` specification (`P1Y6M3DT12H20M30S` or `"1 year 6 months"`); `null` clears regardless of time. Paging is `LIMIT n OFFSET m` on every driver.

## Runtime Tasks

```php
$cron->addRuntimeTask(TCronTask $task): void       // queues task for onEndRequest; AutoRemove defaults to true
$cron->removeRuntimeTask(string|TCronTask): void
$cron->getRuntimeTasks(): ?TCronTask[]
$cron->clearRuntimeTasks(): void
$cron->executeRuntimeTasks($sender, $param): int   // handler called by onEndRequest
$cron->deferTask(\Closure $closure, ?string $name = null): TClosureCronTask   // 4.4.0
```

The first call to `addRuntimeTask()` automatically attaches `executeRuntimeTasks` to `TApplication::onEndRequest`. `addRuntimeTask()` alone does not persist the task, so it has no cron fallback; `addTask($task, true)` or `deferTask()` persists it as well, so a missed `onEndRequest` is run by the next shell cron. A task with `AutoRemove` is deleted from the table once it runs (`updateTaskInfo()`), so it does not run twice.

## Multi-host and daemon

- **Claim.** `updateTaskInfo()` updates `processcount`, `lastexectime`, `nextexectime`, and `options` with `WHERE name=:name AND active IS NOT NULL AND nextexectime = :seen`, where `:seen` is the value this process read; when no row is affected another process ran the trigger first, the task's count and time are restored, and `runTask()` skips it with an INFO log entry. A runtime task without a row is always claimed. `logCronTask()` runs after the claim, so a log row records the run's count and time.
- **Daemon.** See [TCronModule](TCronModule.md#daemon-mode-440). `getNextPendingTime()` is `MIN(nextexectime)` of the active rows; `onDaemonIdle()` calls `deactivateDbConnection()` and `getDbConnectionActivationType()` is `true`, so the connection reopens on the next query. Hosts that do not share a wake transport see each other's new tasks within `DaemonIdle`.

## Permissions (IPermissions)

| Constant | Value | Controls |
|----------|-------|---------|
| `PERM_CRON_ADD_TASK` | `'cron_add_task'` | `dyAddTask` (own tasks, `TUserOwnerRule`) |
| `PERM_CRON_ADD_TASK_GLOBAL` | `'cron_add_task_global'` | `dyAddTaskGlobal` (tasks for any user), 4.4.0 |
| `PERM_CRON_UPDATE_TASK` | `'cron_update_task'` | `dyUpdateTask` (own tasks) |
| `PERM_CRON_REMOVE_TASK` | `'cron_remove_task'` | `dyRemoveTask` (own tasks) |
| `PERM_CRON_LOG_READ` | `'cron_log_read'` | `dyGetCronLog`, `dyGetCronLogCount` (own log rows) |
| `PERM_CRON_LOG_DELETE` | `'cron_log_delete'` | `dyClearCronLog`, `dyRemoveCronLogItem` (own log rows) |

Plus the [`TCronModule`](TCronModule.md) permissions `cron_shell`, `cron_read_tasks`, `cron_run_pending`, and `cron_run_task`.

## Dynamic Events

| Event | Signature | Purpose |
|-------|-----------|---------|
| `dyClearCronLog` | `(bool $return, ?\DateInterval $period, array $extraData)` | Guard/alter log deletion |
| `dyGetCronLog` | `(bool $return, ?string $name, int $pageSize, int $offset, ?bool $sortingDesc, ?string $username, array $extraData)` | Guard/alter log retrieval |
| `dyGetCronLogCount` | `(bool $return, ?string $name, ?string $username, array $extraData)` | Guard/alter count |
| `dyRemoveCronLogItem` | `(bool $return, int $taskUID, array $extraData)` | Guard removal |
| `dyAddTask` | `(bool $return, TCronTask $task, bool $runtime, array $extraData)` | Guard/short-circuit add of an own task |
| `dyAddTaskGlobal` | `(bool $return, TCronTask $task, bool $runtime, array $extraData)` | Guard/short-circuit add of any user's task |
| `dyUpdateTask` | `(bool $return, TCronTask $task, array $extraData)` | Guard/short-circuit update |
| `dyRemoveTask` | `(bool $return, TCronTask\|string $untask, array $extraData)` | Guard/short-circuit remove |

`$extraData` carries `['extra' => ['username' => ...]]` for the owner rule.

## fxGetCronTaskInfos

`TDbCronManager` auto-registers its own cleanup task (`TDbCronCleanLogTask`) via this global event:

```php
public function fxGetCronTaskInfos($cron, $param): TCronTaskInfo
{
    return new TCronTaskInfo('cronclean', TDbCronCleanLogTask::class, $this->getId(), ...);
}
```

## CLI

```bash
php prado-cli.php /app cron [name] [@user]   # list tasks (the default action, 4.4.0)
php prado-cli.php /app cron/run              # run pending tasks; the system crontab entry
php prado-cli.php /app cron/add <name> <task-id> <schedule> [properties...]
php prado-cli.php /app cron/update <name> [properties...]
php prado-cli.php /app cron/remove <name>
php prado-cli.php /app cron/log [name] [@user]
php prado-cli.php /app cron/flush [period] [name] [@user]
php prado-cli.php /app cron/migrate          # create the table when missing; add the 4.4.0 nextexectime column to an older one
```


## Patterns & Gotchas

- **Config tasks vs DB tasks** — tasks declared in `application.xml` (config tasks) coexist with DB tasks. Their names must not collide; loading the tasks throws `TConfigurationException` if they do.
- **Stale config-task rows** — `setPersistentData()` removes the shadow rows (`active = 0`) of config tasks whose config entry has since been removed, on its first load.
- **`active` field semantics** — `NULL` = log row (not a task), `0` = config-task shadow row, `1` = active DB task.
- **Singleton recommendation** — unlike `TCronModule`, only one `TDbCronManager` should be registered per application (hence the rename from `TDbCronModule`).
- **Default SQLite** — if `ConnectionID` is omitted, a SQLite file named `cron.jobs` in the runtime path is used.
- **Test harness** — `tests/unit/Harness/Util/Cron/TTestDbCronManager` pins the clock (`fakeNow`, `fakeMicrotime`) and records wake signals and sleeps instead of performing them.

## See Also

- [`TCronModule`](TCronModule.md) — base cron scheduler
- [`TDbCronCleanLogTask`](TDbCronCleanLogTask.md) — built-in log cleanup task
- [`TClosureCronTask`](TClosureCronTask.md) — the task `deferTask()` creates
- [`TShellDbCronAction`](TShellDbCronAction.md) — CLI management
