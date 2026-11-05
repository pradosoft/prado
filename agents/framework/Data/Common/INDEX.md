# Data/Common/INDEX.md

### Directories
[framework](../../INDEX.md) / [Data](../INDEX.md) / **`Common`**

### Driver Subdirectories

| Directory | Purpose (eg. Driver and Name) |
|---|---|---|
| [`../`](../INDEX.md)] | Data Directory |
| [`Firebird/`](Firebird/INDEX.md) | Firebird / InterBase - `firebird`, `interbase` |
| [`Ibm/`](Ibm/INDEX.md) | IBM DB2 - `ibm` |
| [`Mssql/`](Mssql/INDEX.md) | SQL Server - `mssql`, `sqlsrv`, `dblib` |
| [`Mysql/`](Mysql/INDEX.md) | MySQL / MariaDB - `mysql`, `mysqli` |
| [`Oracle/`](Oracle/INDEX.md) | Oracle - `oci` |
| [`Pgsql/`](Pgsql/INDEX.md) | PostgreSQL - `pgsql` |
| [`Sqlite/`](Sqlite/INDEX.md) | SQLite - `sqlite`, `sqlite2` |

Each subdirectory contains `T{Driver}MetaData`, `T{Driver}CommandBuilder`, `T{Driver}TableInfo`, and `T{Driver}TableColumn`.

## Purpose

Driver-agnostic base classes for database schema introspection and SQL query building. Each database driver (MySQL, PostgreSQL, SQLite, MSSQL, Oracle) has a subdirectory extending these abstractions.

## Top-Level Files

- **[`IDataMetaData`](./IDataMetaData.md)** — Interface for database metadata handlers. Implemented by `TDbMetaData` and all its driver subclasses. Third-party drivers register a class name implementing this interface via the `fxDataGetMetaDataClass` event. (@since 4.3.3)

- **[`IDataCommandBuilder`](./IDataCommandBuilder.md)** — Interface for CRUD command builders. Implemented by `TDbCommandBuilder` and all driver-specific subclasses. Defines `createFindCommand()`, `createInsertCommand()`, `createUpdateCommand()`, `createDeleteCommand()`, `createCountCommand()`, and `applyLimitOffset()`. (@since 4.3.3)

- **[`IDataTableInfo`](./IDataTableInfo.md)** — Interface for table schema metadata. Implemented by `TDbTableInfo` and driver-specific subclasses. Defines `getTableName()`, `getColumns()`, `getPrimaryKeys()`, `getForeignKeys()`, `createCommandBuilder()`. (@since 4.3.3)

- **`TDbMetaData`** — Abstract base for schema introspection. Implements `IDataMetaData`. Key method: `getTableInfo($tableName)` returns a `TDbTableInfo` (cached per connection). Static factory: `TDbMetaData::getInstance($connection)` returns the correct driver subclass based on the PDO driver name. Subclasses implement `createTableInfo($tableName)` and `findTableNames()`.

- **`TDbCommandBuilder`** — Base query builder for a specific table. Implements `IDataCommandBuilder`. Properties: `DbConnection`, `TableInfo`. Methods:
  - `createFindCommand($where, $params, $ordering, $limit, $offset)` — SELECT
  - `createInsertCommand($data)` — INSERT
  - `createUpdateCommand($data, $where, $params)` — UPDATE
  - `createDeleteCommand($where, $params)` — DELETE
  - `createCountCommand($where, $params)` — SELECT COUNT(*)
  - `applyLimitOffset($sql, $limit, $offset)` — driver-specific LIMIT/OFFSET

- **`TDbTableInfo`** — Schema data for one table. Implements `IDataTableInfo`. Properties: `TableName`, `Columns` (map of `TDbTableColumn`), `PrimaryKeys`, `ForeignKeys`. Method: `createCommandBuilder($connection)` returns the appropriate `TDbCommandBuilder`.

- **`TDbTableColumn`** — Schema data for one column. Properties: `ColumnName`, `ColumnId`, `DbType`, `PhpType`, `IsPrimaryKey`, `IsExcluded`, `AllowNull`, `DefaultValue`, `SequenceName`. Method: `getAutoIncrement()`.

- **[`IDbHasSchema`](./IDbHasSchema.md)** — Marker interface. Implemented by `TDbTableInfo` subclasses for drivers that support a schema namespace (MySQL, PostgreSQL, MSSQL, IBM DB2, Oracle). `TDbTableInfo::getSchemaName()` returns `null` for drivers that do not implement it (SQLite, Firebird).

## Patterns & Gotchas

- **Always go through `TDbMetaData::getInstance($conn)`** — never instantiate a driver class directly; the factory selects the right one from the PDO driver name, and handles the third-party `fxDataGetMetaDataClass` fallback.
- **`getTableInfo()` caches** — repeated calls for the same table return the cached object. If schema changes at runtime, the cache must be invalidated.
- **Identifier quoting** — each driver quotes identifiers differently (backticks, brackets, double-quotes). Use `TDbCommandBuilder`'s quoting methods rather than constructing quoted names manually.
- **`IsExcluded`** on a column — columns marked excluded are omitted from INSERT/UPDATE (e.g., computed columns, read-only views).
