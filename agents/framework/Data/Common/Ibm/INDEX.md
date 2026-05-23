# Data/Common/Ibm/INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Directories

[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [Common](../INDEX.md) / **`Ibm`**

| Directory | Purpose |
|---|---|
| [`../`](../INDEX.md) | Data Common Directory |

## Purpose

IBM DB2 driver-specific implementations of the database metadata and query-builder abstractions. Requires the `pdo_ibm` PHP extension. Tested against DB2 LUW 9.7+.

Column metadata is retrieved from `SYSCAT.COLUMNS`; constraints from `SYSCAT.KEYCOLUSE`, `SYSCAT.TABCONST`, and `SYSCAT.REFERENCES`. Schema (owner) is required; defaults to `CURRENT SCHEMA` when not specified.

## Classes

- **[`TIbmMetaData`](./TIbmMetaData.md)** — Extends `TDbMetaData`. Reads `SYSCAT.*` views to build table/column metadata. Supports schema-qualified table names (`schema.table`). `DefaultSchema` defaults to `VALUES CURRENT SCHEMA`.

- **[`TIbmTableInfo`](./TIbmTableInfo.md)** — Extends `TDbTableInfo`. Implements [`IDbHasSchema`](../IDbHasSchema.md). `getTableFullName()` returns `"SCHEMA"."TABLE"`.

- **[`TIbmTableColumn`](./TIbmTableColumn.md)** — Extends `TDbTableColumn`. Maps DB2 SQL types to PHP primitive types. Exposes `getAutoIncrement()` / `hasSequence()` for identity columns.

- **[`TIbmCommandBuilder`](./TIbmCommandBuilder.md)** — Extends `TDbCommandBuilder`. Implements DB2 row-limiting: `FETCH FIRST n ROWS ONLY` for limit-only, and a `ROW_NUMBER()` subquery for limit+offset (compatible with DB2 LUW 9.x+). Retrieves identity values via `IDENTITY_VAL_LOCAL()`.

## Conventions

- DB2 uses double-quotes `"` for identifier quoting.
- Identifiers must not contain double-quote characters (validated by `assertIdentifier()`).
- Identity column detection: `SYSCAT.COLUMNS.IDENTITY = 'Y'`.
- `findTableNames()` returns only base tables (`TYPE = 'T'`), not views.
