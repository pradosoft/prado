# Data/Common/Sqlite/INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Directories

[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [Common](../INDEX.md) / **`Sqlite`**

| Directory | Purpose |
|---|---|
| [`../`](../INDEX.md) | Data Common Directory |

## Purpose

SQLite driver-specific implementations of the database metadata and query-builder abstractions.

## Classes

- **[`TSqliteMetaData`](./TSqliteMetaData.md)** — Extends `TDbMetaData`. Uses `PRAGMA table_info()` and `PRAGMA foreign_key_list()` to introspect table structure: `notnull` sets `AllowNull`, `pk` positions give the (composite) primary key, and a lone `INTEGER PRIMARY KEY` auto-increments.

- **`TSqliteTableInfo`** — Extends `TDbTableInfo`. SQLite-specific table metadata; no schema namespace (SQLite uses attached databases instead).

- **[`TSqliteTableColumn`](./TSqliteTableColumn.md)** — Extends `TDbTableColumn`. `getPHPType()` maps the declared type by name, then by the SQLite affinity rules (`int` → integer, `char`/`clob`/`text` → string, `real`/`floa`/`doub` → float). Reports a lone `INTEGER PRIMARY KEY` as the implicit `rowid` auto-increment.

- **`TSqliteCommandBuilder`** — Extends `TDbCommandBuilder`. Generates SQLite dialect: double-quote or backtick identifier quoting, `LIMIT`/`OFFSET`, `INSERT OR REPLACE` for upsert, `last_insert_rowid()`.

## Conventions

- SQLite uses dynamic/affinity typing — `getDbType()` returns the declared affinity, not a strict SQL type.
- `INTEGER PRIMARY KEY` is always auto-increment (maps to `rowid`); no separate sequence needed.
- SQLite does not support `ALTER TABLE … DROP COLUMN` in older versions; schema migrations are often done by recreating the table.
