# Util/Mutex/TDbMutex

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Mutex](./INDEX.md) / **`TDbMutex`**

## Class Info
**Location:** `framework/Util/Mutex/TDbMutex.php`
**Namespace:** `Prado\Util\Mutex`
**Extends:** [`TBaseDbMutex`](TBaseDbMutex.md)
**Since:** 4.4.0

## Overview
The universal database mutex. On first use `getDriverMutex()` creates the mutex that `getServerMutexClass()` names for the connection's driver, gives it this connection and `KeyPrefix`, turns its `AutoRelease` off, and routes `acquireLock()`/`releaseLock()` to its public `acquire($name)`/`release($name)`. A driver with no server lock (sqlite, firebird, ibm) routes to [`TDbTableMutex`](TDbTableMutex.md) while `TableFallback` is true; otherwise it throws `dbmutex_driver_unsupported`.

| Driver | Routed mutex |
|---|---|
| `mysql` | [`TMysqlMutex`](TMysqlMutex.md) |
| `pgsql` | [`TPgsqlMutex`](TPgsqlMutex.md) |
| `oci` | [`TOracleMutex`](TOracleMutex.md) |
| `sqlsrv`, `dblib`, `mssql` | [`TMssqlMutex`](TMssqlMutex.md) |
| other | [`TDbTableMutex`](TDbTableMutex.md) with `TableFallback` |

Static helpers take an `IDataConnection`, a DSN (`'pgsql:host=…'`), or a driver name: `getDriverName()`, `getServerMutexClass()` (first of `SERVER_MUTEX_CLASSES` whose `isDriverSupported()` is true, or null), and `getIsServerLockAvailable()`. They report the driver only; a server lock can still need privileges, such as `EXECUTE` on `DBMS_LOCK`.

Setting `KeyPrefix` or a lock-table property passes it to an existing routed mutex. A new `DbConnection` or `ConnectionID` discards the routed mutex so the next use chooses again. Without `ConnectionID` the connection is SQLite `runtime/sqlite3.mutex`, the same file as `TDbTableMutex`. The routed mutex listens to `fxRestoreAfterFork` itself.

## Properties

| Property | Default | Meaning |
|---|---|---|
| `TableFallback` | true | route a driver without a server lock to `TDbTableMutex` |
| `Expire` | null | lock-table lease seconds; null keeps the `TDbTableMutex` default |
| `MutexTableName` | null | lock table name; null keeps `pradomutex` |
| `AutoCreateMutexTable` | null | create the lock table; null keeps true |
