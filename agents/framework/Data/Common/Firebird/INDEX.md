# Data/Common/Firebird/INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Directories

[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [Common](../INDEX.md) / **`Firebird`**

| Directory | Purpose |
|---|---|
| [`../`](../INDEX.md) | Data Common Directory |

## Purpose

Firebird driver-specific implementations of the database metadata and query-builder abstractions. Requires the `pdo_firebird` PHP extension. Tested against Firebird 2.5+.

Firebird has **no schema namespace**; all tables are unique within a database file. Unquoted identifiers are stored as uppercase in Firebird system tables; this driver uppercases for `RDB$` system queries and returns lowercase identifiers to PHP.

## Classes

- **[`TFirebirdMetaData`](./TFirebirdMetaData.md)** — Extends `TDbMetaData`. Reads `RDB$RELATION_FIELDS`, `RDB$FIELDS`, `RDB$RELATION_CONSTRAINTS`, and `RDB$RELATIONS` to build table/column metadata. Handles the full `RDB$FIELD_TYPE` code-to-SQL-type mapping including Firebird 3+ and 4+ types (`BOOLEAN`, `DECFLOAT`, `INT128`, `TIMESTAMP WITH TIME ZONE`).

- **[`TFirebirdTableInfo`](./TFirebirdTableInfo.md)** — Extends `TDbTableInfo`. Does **not** implement `IDbHasSchema` (Firebird has no schema). `getTableFullName()` returns the double-quoted, uppercased table name.

- **[`TFirebirdTableColumn`](./TFirebirdTableColumn.md)** — Extends `TDbTableColumn`. Maps Firebird type codes to PHP primitive types. Exposes `getAutoIncrement()` and `hasSequence()` for Firebird 3+ IDENTITY columns.

- **[`TFirebirdCommandBuilder`](./TFirebirdCommandBuilder.md)** — Extends `TDbCommandBuilder`. Implements `SELECT FIRST n SKIP m` pagination (inserted after `SELECT` keyword, handles `SELECT DISTINCT`). Retrieves last identity value via `RDB$GET_CONTEXT('SYSTEM', 'LAST_INSERT_ID')`.

## Conventions

- Firebird uses double-quotes `"` for identifier quoting.
- `IDENTITY_TYPE` in `RDB$RELATION_FIELDS`: `0` = `ALWAYS`, `1` = `BY DEFAULT` (Firebird 3+).
- BLOB sub-type `1` is TEXT; sub-type `0` is binary.
- Numeric scale is stored as a negative integer in Firebird; the driver converts to the absolute value.
