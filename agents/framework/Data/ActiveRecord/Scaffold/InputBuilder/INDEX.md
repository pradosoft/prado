# Data/ActiveRecord/Scaffold/InputBuilder/INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Directories

[framework](./INDEX.md) / [Data](./Data/INDEX.md) / [ActiveRecord](./Data/ActiveRecord/INDEX.md) / [Scaffold](./Data/ActiveRecord/Scaffold/INDEX.md) / [InputBuilder](./Data/ActiveRecord/Scaffold/InputBuilder/INDEX.md) / **`InputBuilder/INDEX.md`**

| Directory | Purpose |
|---|---|
| [`../`](../INDEX.md) | ActiveRecord Scaffold Directory |

## Purpose

Driver-specific input control builders for the Active Record scaffold UI. Each class maps database column types to appropriate Prado form controls (text box, checkbox, date picker, etc.) for auto-generated CRUD edit views.

## Classes

- **`TScaffoldInputBase`** — Abstract base. Declares the interface: `createInputControl($column)` returns a `TControl` instance appropriate for the column type. Also handles common attributes (required, max-length).

- **`TScaffoldInputCommon`** — Shared logic used across all driver-specific builders: maps generic SQL types (`varchar`, `int`, `bool`, `date`, `text`, etc.) to Prado controls. Extended by each driver subclass.

- **`TMysqlScaffoldInput`** — MySQL-specific mappings: handles `enum`, `set`, `tinyint(1)` as checkbox, MySQL date/datetime/timestamp types.

- **`TPgsqlScaffoldInput`** — PostgreSQL-specific: handles `boolean`, `serial`, `bytea`, array types, PostgreSQL `date`/`timestamp`.

- **`TMssqlScaffoldInput`** — SQL Server-specific: handles `bit`, `uniqueidentifier`, `money`, `nvarchar`, `datetime2`.

- **`TSqliteScaffoldInput`** — SQLite-specific: handles SQLite's loose typing (`INTEGER`, `REAL`, `TEXT`, `BLOB`).

- **`TIbmScaffoldInput`** — IBM DB2-specific: handles DB2 column type conventions (`INTEGER`, `BIGINT`, `DECIMAL`, `TIMESTAMP`, `DATE`, `TIME`, `CHAR`, `VARCHAR`).

## Patterns & Gotchas

- The correct subclass is selected automatically based on the database driver detected by `TDbMetaData::createMetaData()`.
- Override `createInputControl()` in a subclass to customise scaffold inputs for a specific column or type.
- Column metadata is provided as a `TDbTableColumn` (driver-specific subclass); check `getDbType()` for the raw SQL type string.
