# Data/Common/Sqlite/INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Subdirectories

| Directory | Purpose |
|---|---|---|
| [`../`](../INDEX.md)] | Data Common Directory |

## Purpose

SQLite driver-specific implementations of the database metadata and query-builder abstractions.

## Classes

- **`TSqliteMetaData`** — Extends `TDbMetaData`. Uses `PRAGMA table_info()` and `PRAGMA index_list()` / `PRAGMA index_info()` to introspect table structure. Handles SQLite's flexible typing and `INTEGER PRIMARY KEY` autoincrement behaviour.

- **`TSqliteTableInfo`** — Extends `TDbTableInfo`. SQLite-specific table metadata; no schema namespace (SQLite uses attached databases instead).

- **`TSqliteTableColumn`** — Extends `TDbTableColumn`. Maps SQLite's affinity types (`INTEGER`, `REAL`, `TEXT`, `BLOB`, `NUMERIC`) to PHP types. Detects `INTEGER PRIMARY KEY` as the implicit `rowid` auto-increment.

- **`TSqliteCommandBuilder`** — Extends `TDbCommandBuilder`. Generates SQLite dialect: double-quote or backtick identifier quoting, `LIMIT`/`OFFSET`, `INSERT OR REPLACE` for upsert, `last_insert_rowid()`.

## Conventions

- SQLite uses dynamic/affinity typing — `getDbType()` returns the declared affinity, not a strict SQL type.
- `INTEGER PRIMARY KEY` is always auto-increment (maps to `rowid`); no separate sequence needed.
- SQLite does not support `ALTER TABLE … DROP COLUMN` in older versions; schema migrations are often done by recreating the table.
