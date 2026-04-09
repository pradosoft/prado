# SUMMARY.md

SQLite driver-specific implementations of database metadata and query-builder abstractions.

## Classes

- **`TSqliteMetaData`** — Extends `TDbMetaData`; uses `PRAGMA table_info()` and `PRAGMA index_list()`/`PRAGMA index_info()`; handles SQLite's flexible typing and `INTEGER PRIMARY KEY` autoincrement.

- **`TSqliteTableInfo`** — Extends `TDbTableInfo`; SQLite-specific table metadata.

- **`TSqliteTableColumn`** — Extends `TDbTableColumn`; maps SQLite's affinity types (`INTEGER`, `REAL`, `TEXT`, `BLOB`, `NUMERIC`) to PHP types.

- **`TSqliteCommandBuilder`** — Extends `TDbCommandBuilder`; generates SQLite dialect with double-quote/backtick quoting, `LIMIT`/`OFFSET`, `INSERT OR REPLACE`, `last_insert_rowid()`.
