# Data/ActiveRecord/Scaffold/InputBuilder/SUMMARY.md

Driver-specific input control builders mapping database column types to Prado form controls for auto-generated CRUD edit views.

## Classes

- **`TScaffoldInputBase`** — Abstract base; interface: `createInputControl($column)` returns `TControl` appropriate for column type.

- **`TScaffoldInputCommon`** — Shared logic mapping generic SQL types (`varchar`, `int`, `bool`, `date`, `text`) to Prado controls.

- **`TMysqlScaffoldInput`** — MySQL-specific: handles `enum`, `set`, `tinyint(1)` as checkbox, MySQL date types.

- **`TPgsqlScaffoldInput`** — PostgreSQL-specific: handles `boolean`, `serial`, `bytea`, array types.

- **`TMssqlScaffoldInput`** — SQL Server-specific: handles `bit`, `uniqueidentifier`, `money`, `nvarchar`.

- **`TSqliteScaffoldInput`** — SQLite-specific: handles SQLite's affinity types (`INTEGER`, `REAL`, `TEXT`, `BLOB`).

- **`TIbmScaffoldInput`** — IBM DB2-specific: handles `INTEGER`, `BIGINT`, `DECIMAL`, `TIMESTAMP`, etc.
