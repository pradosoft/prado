# SUMMARY.md

MySQL/MariaDB driver-specific implementations of database metadata and query-builder abstractions.

## Classes

- **`TMysqlMetaData`** — Extends `TDbMetaData`; uses `SHOW FULL COLUMNS FROM` and `SHOW INDEX FROM` for introspection; handles MySQL-specific types including `enum`, `set`, unsigned numerics.

- **`TMysqlTableInfo`** — Extends `TDbTableInfo`; stores MySQL-specific attributes: engine, charset, collation.

- **`TMysqlTableColumn`** — Extends `TDbTableColumn`; adds MySQL-specific attributes: `auto_increment`, `unsigned`, `zerofill`, `enum`/`set` values list.

- **`TMysqlCommandBuilder`** — Extends `TDbCommandBuilder`; generates MySQL dialect with backtick quoting, `LIMIT`/`OFFSET`, `REPLACE INTO`, `LAST_INSERT_ID()`.
