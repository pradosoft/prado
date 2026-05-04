# Data/Common/Pgsql/SUMMARY.md

PostgreSQL driver-specific implementations of database metadata and query-builder abstractions.

## Classes

- **`TPgsqlMetaData`** — Extends `TDbMetaData`; queries `information_schema` and PostgreSQL system catalogs; handles schemas, sequences, array types.

- **`TPgsqlTableInfo`** — Extends `TDbTableInfo`; stores PostgreSQL-specific attributes including schema name and OID.

- **`TPgsqlTableColumn`** — Extends `TDbTableColumn`; handles `serial`/`bigserial`, `boolean`, `bytea`, `array`, `json`/`jsonb`, `uuid`.

- **`TPgsqlCommandBuilder`** — Extends `TDbCommandBuilder`; generates PostgreSQL dialect with double-quote quoting, `LIMIT`/`OFFSET`, `INSERT ... ON CONFLICT DO UPDATE`, `currval()`/`RETURNING`.
