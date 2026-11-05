# Data/Common/Mssql/INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Directories

[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [Common](../INDEX.md) / **`Mssql`**

| Directory | Purpose |
|---|---|
| [`../`](../INDEX.md) | Data Common Directory |

## Purpose

SQL Server (MSSQL) driver-specific implementations of the database metadata and query-builder abstractions.

## Classes

The four classes are deprecated. Each is an empty subclass of its `SqlSrv` counterpart and keeps the 4.3.2 class name working.

| Class | Extends |
|---|---|
| `TMssqlMetaData` | [`TSqlSrvMetaData`](../SqlSrv/TSqlSrvMetaData.md) |
| `TMssqlTableInfo` | [`TSqlSrvTableInfo`](../SqlSrv/TSqlSrvTableInfo.md) |
| `TMssqlTableColumn` | [`TSqlSrvTableColumn`](../SqlSrv/TSqlSrvTableColumn.md) |
| `TMssqlCommandBuilder` | [`TSqlSrvCommandBuilder`](../SqlSrv/TSqlSrvCommandBuilder.md) |

## Conventions

- New code uses the `SqlSrv` classes. The `SqlSrv` index documents the SQL Server dialect.
