# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Purpose

Firebird (and InterBase) driver-specific implementations of the database metadata and query-builder abstractions.

Requires the `pdo_firebird` PHP extension. PDO driver names: `firebird`, `interbase`.

## Classes

- **`TFirebirdMetaData`** — Extends `TDbMetaData`. Queries `RDB$RELATION_FIELDS`, `RDB$FIELDS`, `RDB$RELATIONS`, `RDB$RELATION_CONSTRAINTS`, `RDB$INDEX_SEGMENTS`, and `RDB$REF_CONSTRAINTS` to build table/column metadata. Firebird has no schema namespace; table names are unique within a database file. Normalises identifiers to uppercase for system table queries; returns lowercase IDs to PHP.

- **`TFirebirdTableInfo`** — Extends `TDbTableInfo`. `getTableFullName()` returns `"TABLENAME"` (double-quoted uppercase). No schema name.

- **`TFirebirdTableColumn`** — Extends `TDbTableColumn`. Maps Firebird `RDB$FIELD_TYPE` integer codes to type name strings (`SMALLINT`, `INTEGER`, `BIGINT`, `FLOAT`, `DOUBLE PRECISION`, `CHAR`, `VARCHAR`, `DATE`, `TIME`, `TIMESTAMP`, `BOOLEAN`, `BLOB`, `TEXT`, `DECFLOAT(16)`, `DECFLOAT(34)`, etc.). `getAutoIncrement()` reflects Firebird 3+ `IDENTITY` columns (`RDB$IDENTITY_TYPE`).

- **`TFirebirdCommandBuilder`** — Extends `TDbCommandBuilder`. Firebird-specific row limiting via `SELECT FIRST n SKIP m ...` syntax (injected after `SELECT` keyword, not appended). `getLastInsertID()` uses `SELECT RDB$GET_CONTEXT('SYSTEM', 'LAST_INSERT_ID') FROM RDB$DATABASE` (Firebird 3+).

## Conventions

- Firebird uses double-quotes for case-sensitive identifier quoting; unquoted identifiers are stored uppercase.
- Firebird has **no schema** — tables are identified by name within the database file. `findTableNames()` ignores the `$schema` parameter.
- `IDENTITY` columns (`GENERATED ALWAYS/BY DEFAULT AS IDENTITY`) are Firebird 3+; older databases used sequences + triggers. The command builder only retrieves last-insert-ID for identity columns.
- `BLOB SUB_TYPE 1` is text (`TEXT`); `BLOB SUB_TYPE 0` is binary (`BLOB`).
- Numeric scale in Firebird is stored as a **negative** integer in `RDB$FIELD_SCALE`; the metadata class converts it to a positive value.
- Views are identified by `RDB$VIEW_BLR IS NOT NULL` in `RDB$RELATIONS`.
- `FIRST`/`SKIP` clauses are injected after `SELECT`, not appended to the end — any SQL rewriting must account for this.
