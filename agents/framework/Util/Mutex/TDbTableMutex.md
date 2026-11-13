# Util/Mutex/TDbTableMutex

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Mutex](./INDEX.md) / **`TDbTableMutex`**

## Class Info
**Location:** `framework/Util/Mutex/TDbTableMutex.php`
**Namespace:** `Prado\Util\Mutex`
**Extends:** [`TBaseDbMutex`](TBaseDbMutex.md)
**Since:** 4.4.0

## Overview
An attempt deletes the expired row of the name (`expire < now`), then inserts `(lockname, locktoken, expire)`; a primary-key violation is a failed attempt. Release deletes the row with the name and this mutex's token. With `AutoCreateMutexTable`, `ensureMutexTable()` creates `lockname VARCHAR(191) PRIMARY KEY, locktoken CHAR(32), expire INTEGER` on first use; a concurrent create is tolerated. Without `ConnectionID` the table is in SQLite `runtime/sqlite3.mutex`. Time comes from the application clock (`TApplicationClockAwareTrait`). Names over 191 characters become SHA-1. Any driver.

## Cron Maintenance
An attempt only deletes the expired row of its own name, so the row of a name that is never locked again stays. `flushExpired()` deletes every row with `expire < now` and returns the count; it is safe while locks are held, since it removes only rows an attempt would delete. A mutex module with an ID registers the task `dbmutexflushexpired` (`'<id>->flushExpired()'`) through `fxGetCronTaskInfos`; without an ID it returns null. Every TDbTableMutex module registers the same task name, and `cron/add` takes the first; configure a job with the task string for each other module.

## Properties

| Property | Default | Meaning |
|---|---|---|
| `MutexTableName` | 'pradomutex' | the lock table; validated SQL identifier |
| `AutoCreateMutexTable` | true | create the table on first use |
| `Expire` | 30 | lease seconds; at least 1 |
