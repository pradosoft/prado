# Data/Common/SqlSrv/INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Directories

[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [Common](../INDEX.md) / **`SqlSrv`**

| Directory | Purpose |
|---|---|
| [`../`](../INDEX.md) | Data Common Directory |

## Purpose

SQL Server (MSSQL) driver-specific implementations of the database metadata and query-builder abstractions.

## Classes

- **`TSqlSrvMetaData`** — Extends `TDbMetaData`. Queries SQL Server system catalog (`INFORMATION_SCHEMA`, `sys.*`) to build table/column metadata. Handles schema-qualified table names (`schema.table`).

- **`TSqlSrvTableInfo`** — Extends `TDbTableInfo`. SQL Server-specific table metadata; stores schema name separately from table name.

- **`TSqlSrvTableColumn`** — Extends `TDbTableColumn`. Adds SQL Server column attributes: `identity` (auto-increment), `computed`, `xml` columns, `uniqueidentifier`.

- **`TSqlSrvCommandBuilder`** — Extends `TDbCommandBuilder`. Generates SQL Server dialect queries: uses `TOP` for limiting rows (no `LIMIT`/`OFFSET` syntax pre-2012), brackets `[identifier]` quoting, `SCOPE_IDENTITY()` for last insert ID.

## Conventions

- SQL Server uses `[brackets]` for identifier quoting — never backticks or double-quotes.
- `LIMIT`/`OFFSET` is not supported in older SQL Server; the command builder uses `TOP` or `ROW_NUMBER()` windowing instead.
- `identity` columns map to `getAutoIncrement() === true` on `TSqlSrvTableColumn`.
