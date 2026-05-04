# Data/Common/INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Driver Subdirectories

| Directory | Purpose (eg. Driver and Name) |
|---|---|---|
| [`../`](../INDEX.md)] | Data Directory |
| [`Mssql/`](Mssql/INDEX.md) | SQL Server - `mssql`, `sqlsrv`, `dblib` |
| [`Mysql/`](Mysql/INDEX.md) | MySQL / MariaDB - `mysql`, `mysqli` |
| [`Oracle/`](Oracle/INDEX.md) | Oracle - `oci` |
| [`Pgsql/`](Pgsql/INDEX.md) | PostgreSQL - `pgsql` |
| [`Sqlite/`](Sqlite/INDEX.md) | SQLite - `sqlite`, `sqlite2` |

Each subdirectory contains `T{Driver}MetaData`, `T{Driver}CommandBuilder`, `T{Driver}TableInfo`, and `T{Driver}TableColumn`.

## Purpose

Driver-agnostic base classes for database schema introspection and SQL query building. Each database driver (MySQL, PostgreSQL, SQLite, MSSQL, Oracle) has a subdirectory extending these abstractions.

## Top-Level Classes

- **`TDbMetaData`** — Abstract base for schema introspection. Key method: `getTableInfo($tableName)` returns a `TDbTableInfo` (cached per connection). Static factory: `TDbMetaData::createMetaData($connection)` returns the correct driver subclass based on the PDO driver name. Subclasses implement `createTableInfo($tableName)` and `findTableNames()`.

- **`TDbCommandBuilder`** — Base query builder for a specific table. Properties: `DbConnection`, `TableInfo`. Methods:
  - `createFindCommand($where, $params, $ordering, $limit, $offset)` — SELECT
  - `createInsertCommand($data)` — INSERT
  - `createUpdateCommand($data, $where, $params)` — UPDATE
  - `createDeleteCommand($where, $params)` — DELETE
  - `createCountCommand($where, $params)` — SELECT COUNT(*)
  - `applyLimitOffset($sql, $limit, $offset)` — driver-specific LIMIT/OFFSET

- **`TDbTableInfo`** — Schema data for one table. Properties: `TableName`, `Columns` (map of `TDbTableColumn`), `PrimaryKeys`, `ForeignKeys`. Method: `createCommandBuilder($connection)` returns the appropriate `TDbCommandBuilder`.

- **`TDbTableColumn`** — Schema data for one column. Properties: `ColumnName`, `ColumnId`, `DbType`, `PhpType`, `IsPrimaryKey`, `IsExcluded`, `AllowNull`, `DefaultValue`, `SequenceName`. Method: `getAutoIncrement()`.

## Patterns & Gotchas

- **Always go through `TDbMetaData::createMetaData($conn)`** — never instantiate a driver class directly; the factory selects the right one from the PDO driver name.
- **`getTableInfo()` caches** — repeated calls for the same table return the cached object. If schema changes at runtime, the cache must be invalidated.
- **Identifier quoting** — each driver quotes identifiers differently (backticks, brackets, double-quotes). Use `TDbCommandBuilder`'s quoting methods rather than constructing quoted names manually.
- **`IsExcluded`** on a column — columns marked excluded are omitted from INSERT/UPDATE (e.g., computed columns, read-only views).
