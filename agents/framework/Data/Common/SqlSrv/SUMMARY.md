# Data/Common/SqlSrv/SUMMARY.md

SQL Server (MSSQL) driver-specific implementations of database metadata and query-builder abstractions.

## Classes

- **`TSqlSrvMetaData`** — Extends `TDbMetaData`; queries SQL Server system catalog (`INFORMATION_SCHEMA`, `sys.*`); handles schema-qualified table names.

- **`TSqlSrvTableInfo`** — Extends `TDbTableInfo`; stores SQL Server-specific metadata; schema name tracked separately.

- **`TSqlSrvTableColumn`** — Extends `TDbTableColumn`; adds SQL Server column attributes: `identity`, `computed`, `xml` columns, `uniqueidentifier`.

- **`TSqlSrvCommandBuilder`** — Extends `TDbCommandBuilder`; generates SQL Server dialect using `TOP` for limiting, brackets quoting, `SCOPE_IDENTITY()`.
