# Data/Common/Pgsql/INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Subdirectories

| Directory | Purpose |
|---|---|---|
| [`../`](../INDEX.md)] | Data Common Directory |

## Purpose

PostgreSQL driver-specific implementations of the database metadata and query-builder abstractions.

## Classes

- **`TPgsqlMetaData`** — Extends `TDbMetaData`. Queries `information_schema` and PostgreSQL system catalogs (`pg_class`, `pg_attribute`, `pg_constraint`) to build table/column metadata. Handles schemas, sequences, and array types.

- **`TPgsqlTableInfo`** — Extends `TDbTableInfo`. Stores PostgreSQL-specific table attributes including schema name and OID.

- **`TPgsqlTableColumn`** — Extends `TDbTableColumn`. Handles PostgreSQL-specific types: `serial`/`bigserial` (auto-increment via sequences), `boolean`, `bytea`, `array`, `json`/`jsonb`, `uuid`.

- **`TPgsqlCommandBuilder`** — Extends `TDbCommandBuilder`. Generates PostgreSQL dialect: double-quote `"identifier"` quoting, `LIMIT`/`OFFSET`, `INSERT ... ON CONFLICT DO UPDATE` for upsert, `currval()`/`RETURNING` for last insert ID.

## Conventions

- PostgreSQL uses double-quotes for identifier quoting.
- `serial`/`bigserial` columns map to `getAutoIncrement() === true` on `TPgsqlTableColumn`.
- `boolean` is a native type in PostgreSQL; PDO returns it as `'t'`/`'f'` strings — coerce with `TPropertyValue::ensureBoolean()`.
- Schema-qualified table names (`schema.table`) are supported; the default schema is `public`.
