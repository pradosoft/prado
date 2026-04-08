# Data/Common/Mysql/INDEX.md - DATA_COMMON_MYSQL_INDEX.md

This file provides guidance to Agents when working with code in this repository.

## Purpose

MySQL/MariaDB driver-specific implementations of the database metadata and query-builder abstractions.

## Classes

- **`TMysqlMetaData`** — Extends `TDbMetaData`. Uses `SHOW FULL COLUMNS FROM` and `SHOW INDEX FROM` to introspect table structure. Handles MySQL-specific types including `enum`, `set`, and unsigned numeric types.

- **`TMysqlTableInfo`** — Extends `TDbTableInfo`. Stores MySQL-specific table attributes (engine, charset, collation).

- **`TMysqlTableColumn`** — Extends `TDbTableColumn`. Adds MySQL-specific column attributes: `auto_increment`, `unsigned`, `zerofill`, `enum`/`set` values list, `on update CURRENT_TIMESTAMP`.

- **`TMysqlCommandBuilder`** — Extends `TDbCommandBuilder`. Generates MySQL dialect: backtick `` `identifier` `` quoting, `LIMIT`/`OFFSET`, `REPLACE INTO` for upsert, `LAST_INSERT_ID()`.

## Conventions

- MySQL uses backtick `` ` `` for identifier quoting.
- `auto_increment` columns map to `getAutoIncrement() === true` on `TMysqlTableColumn`.
- `enum` column values are accessible via `getEnumValues()` on `TMysqlTableColumn`; the scaffold uses this to render a dropdown.
