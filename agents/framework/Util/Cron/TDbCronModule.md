# TDbCronModule

### Directories

[Util](../) > [Cron](Cron/) > TDbCronModule

**Location:** `framework/Util/Cron/TDbCronModule.php`
**Namespace:** `Prado\Util\Cron`

## Overview

TDbCronModule extends TCronModule with database-backed task storage and management. It supports dynamic task registration, execution logging, failed-task retry logic, and runtime task execution at onEndRequest. Tasks and logs are stored in a configurable database table.

## Key Properties/Methods

- `getConnectionID()` / `setConnectionID($value)` - ID of TDataSourceConfig module for DB connection
- `getDbConnection()` - Gets the database connection (creates SQLite default if not set)
- `getTableName()` / `setTableName($table)` - Database table name (default: 'crontabs')
- `getAutoCreateCronTable()` / `setAutoCreateCronTable($value)` - Auto-create table if not exists
- `getLogCronTasks()` / `setLogCronTasks($log)` - Whether to log task executions
- `getTasks()` - Returns combined config and database tasks
- `getTask($taskName, $checkExisting, $asObject)` - Gets a task by name
- `addTask($task, $runtime)` - Adds a task to the database
- `updateTask($task)` - Updates a task in the database
- `removeTask($untask)` - Removes a task from the database
- `taskExists($name)` - Checks if a task exists in the database
- `addRuntimeTask($task)` - Adds task to run at onEndRequest
- `executeRuntimeTasks()` - Executes all pending runtime tasks
- `clearRuntimeTasks()` - Clears all runtime tasks
- `clearCronLog($seconds)` - Deletes log entries older than specified seconds
- `getCronLog($name, $pageSize, $offset, $sortingDesc)` - Gets cron log entries
- `getCronLogCount($name)` - Gets count of log entries

## See Also

- [TCronModule](../TCronModule.md)
- [TCronTask](TCronTask.md)
- [TCronTaskInfo](TCronTaskInfo.md)
