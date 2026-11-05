# Data/IDataConnection

### Directories
[framework](../INDEX.md) / [Data](./INDEX.md) / **`IDataConnection`**

## Interface Info
**Location:** `framework/Data/IDataConnection.php`
**Namespace:** `Prado\Data`
**Since:** 4.3.3

## Overview

`IDataConnection` defines the common interface for a data-store connection. The concrete implementation for SQL/PDO databases is [`TDbConnection`](./TDbConnection.md), which also implements the PDO-specific extension [`IDbConnection`](./IDbConnection.md).

This interface provides a driver-agnostic API so that application code and PRADO plugins can supply custom connection implementations without coupling to a concrete class.

## Interface Methods

| Method | Description |
|--------|-------------|
| `getDriverName()` | Returns the driver name (e.g. `'mysql'`, `'pgsql'`, `'sqlite'`). |
| `getActive()` | Returns whether the connection is currently open. |
| `setActive($value)` | Opens (`true`) or closes (`false`) the connection. |
| `createCommand($query)` | Creates an [`IDataCommand`](./IDataCommand.md) for the given query (SQL string for SQL connections). |
| `beginTransaction()` | Starts a new transaction; returns an [`IDataTransaction`](./IDataTransaction.md). Each call allocates a new transaction object; throws if a transaction is already active. |
| `getCurrentTransaction()` | Returns the active [`IDataTransaction`](./IDataTransaction.md), or `null` if none is open. |
| `getLastInsertID($sequenceName = '')` | Returns the last insert ID (or sequence value). Sequence name is required for PostgreSQL, IBM DB2, Oracle, and Firebird. |
| `getConnectionString()` | Returns the DSN / connection string (PDO DSN for SQL connections). |
| `quoteString($str)` | Quotes a string value for safe embedding in SQL. |
| `getColumnCase()` | Returns the current column-case mode (`TDbColumnCaseMode`). |
| `setColumnCase($value)` | Sets the column-case mode. |
| `getAttribute($name)` | Returns a PDO attribute value by constant. |
| `setAttribute($name, $value)` | Sets a PDO attribute. |

## See Also

- [`IDbConnection`](./IDbConnection.md) — extends this interface with `getPdoInstance()` for PDO-specific access
- [`IDataCommand`](./IDataCommand.md) — command interface
- [`IDataTransaction`](./IDataTransaction.md) — transaction interface
- [`TDbConnection`](./TDbConnection.md) — SQL/PDO implementation
