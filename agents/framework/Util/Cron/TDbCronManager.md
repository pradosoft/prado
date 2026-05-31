# Util/Cron/TDbCronManager

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TDbCronManager`**

## Class Info
**Location:** `framework/Util/Cron/TDbCronManager.php`
**Namespace:** `Prado\Util\Cron`
**Extends:** [`TCronModule`](TCronModule.md)
**Implements:** [`IDbModule`](../IDbModule.md), `IPermissions`
**Since:** 4.3.3

> **Note:** `TDbCronModule` (since 4.2.0) is now a deprecated alias that extends `TDbCronManager`. Use `TDbCronManager` for all new code.

## Overview
`TDbCronManager` does everything [`TCronModule`](TCronModule.md) does, but stores all tasks and execution logs in a database table (`crontabs` by default). It adds:

- Dynamic task CRUD (`addTask`, `updateTask`, `removeTask`)
- Execution logging to the DB (`LogCronTasks`)
- Runtime task queue — tasks that run at `onEndRequest` (useful when a page-request triggers a one-time background job)
- Permission-gated CLI management via [`TShellDbCronAction`](TShellDbCronAction.md)
- Auto-creates the DB table if needed

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
        ['Name' => 'dbcacheclean', 'Schedule' => '0 0 * * *', 'Task' => 'dbcache->flushCacheExpired(true)'],
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
| `username` | VARCHAR(127) | Run-as user |
| `options` | MEDIUMTEXT | Serialized `TCronTask` object |
| `processcount` | INT | Times task has run |
| `lastexectime` | VARCHAR(20) | Unix timestamp of last run |
| `active` | BOOLEAN | `1` = DB task, `0` = config task shadow, `NULL` = log entry |

Active rows (`active IS NOT NULL`) are task definitions; rows with `active IS NULL` are execution log entries.

## Key Properties

| Property | Default | Description |
|----------|---------|-------------|
| `ConnectionID` | — | ID of `TDataSourceConfig` module; frozen after `init()` |
| `TableName` | `'crontabs'` | DB table for tasks and logs; frozen after `init()` |
| `AutoCreateCronTable` | `true` | Create table automatically if absent; frozen after `init()` |
| `LogCronTasks` | `true` | Insert a log row per task execution |

## Task CRUD

```php
$cron->addTask(TCronTask $task, bool $runtime = false): bool
$cron->updateTask(TCronTask $task): bool
$cron->removeTask(string|TCronTask $untask): bool
$cron->getTask(string $name, bool $checkExisting = true, bool $asObject = true): ?TCronTask
$cron->getTasks(): TCronTask[]          // DB tasks + config tasks merged
$cron->taskExists(string $name): bool
```

`NAME_VALIDATOR_REGEX` enforces that names contain no whitespace, backticks, quotes, `<>`, `%`, and do not start with `*`.

## Log Management

```php
$cron->getCronLog(?string $name, int $pageSize, int $offset, ?bool $sortingDesc = null): array
$cron->getCronLogCount(?string $name): int
$cron->clearCronLog(int $seconds): int      // deletes entries older than $seconds ago
$cron->removeCronLogItem(int $taskUID): void
```

## Runtime Tasks

```php
$cron->addRuntimeTask(TCronTask $task): void       // queues task for onEndRequest
$cron->removeRuntimeTask(string|TCronTask): void
$cron->getRuntimeTasks(): ?TCronTask[]
$cron->clearRuntimeTasks(): void
$cron->executeRuntimeTasks($sender, $param): int   // handler called by onEndRequest
```

The first call to `addRuntimeTask()` automatically attaches `executeRuntimeTasks` to `TApplication::onEndRequest`.

## Permissions (IPermissions)

| Constant | Value | Controls |
|----------|-------|---------|
| `PERM_CRON_LOG_READ` | `'cron_log_read'` | `dyGetCronLog`, `dyGetCronLogCount` |
| `PERM_CRON_LOG_DELETE` | `'cron_log_delete'` | `dyClearCronLog`, `dyRemoveCronLogItem` |
| `PERM_CRON_ADD_TASK` | `'cron_add_task'` | `dyAddTask` |
| `PERM_CRON_UPDATE_TASK` | `'cron_update_task'` | `dyUpdateTask` |
| `PERM_CRON_REMOVE_TASK` | `'cron_remove_task'` | `dyRemoveTask` |

## Dynamic Events

| Event | Signature | Purpose |
|-------|-----------|---------|
| `dyClearCronLog` | `(bool $return, int $seconds)` | Guard/alter log deletion |
| `dyGetCronLog` | `(bool $return, ?string $name, int $pageSize, int $offset, $sortingDesc)` | Guard/alter log retrieval |
| `dyGetCronLogCount` | `(bool $return, ?string $name)` | Guard/alter count |
| `dyRemoveCronLogItem` | `(bool $return, int $taskUID)` | Guard removal |
| `dyAddTask` | `(bool $return, TCronTask $task, bool $runtime)` | Guard/short-circuit add |
| `dyUpdateTask` | `(bool $return, TCronTask $task, array $extraData)` | Guard/short-circuit update |
| `dyRemoveTask` | `(bool $return, TCronTask\|string $untask, array $extraData)` | Guard/short-circuit remove |

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
php prado-cli.php /app db-cron          # list pending tasks
php prado-cli.php /app db-cron run      # run pending tasks
php prado-cli.php /app db-cron add <name> <task-id> <schedule> [properties...]
php prado-cli.php /app db-cron update <name> [properties...]
php prado-cli.php /app db-cron remove <name>
```


## Patterns & Gotchas

- **Config tasks vs DB tasks** — tasks declared in `application.xml` (config tasks) coexist with DB tasks. Their names must not collide; `getTasks()` throws `TConfigurationException` if they do.
- **Stale config-task rows** — `filterStaleTasks()` removes DB rows that were created for config tasks whose config entry has since been removed.
- **`active` field semantics** — `NULL` = log row (not a task), `0` = config-task shadow row, `1` = active DB task.
- **Singleton recommendation** — unlike `TCronModule`, only one `TDbCronManager` should be registered per application (hence the rename from `TDbCronModule`).
- **Default SQLite** — if `ConnectionID` is omitted, a SQLite file named `cron.jobs` in the runtime path is used.

## See Also

- [`TCronModule`](TCronModule.md) — base cron scheduler
- [`TDbCronModule`](TDbCronModule.md) — deprecated alias
- [`TDbCronCleanLogTask`](TDbCronCleanLogTask.md) — built-in log cleanup task
- [`TShellDbCronAction`](TShellDbCronAction.md) — CLI management
