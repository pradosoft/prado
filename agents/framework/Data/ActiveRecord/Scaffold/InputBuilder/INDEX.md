# Data/ActiveRecord/Scaffold/InputBuilder/INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Directories

[framework](../../../../INDEX.md) / [Data](../../../INDEX.md) / [ActiveRecord](../../INDEX.md) / [Scaffold](../INDEX.md) / **`InputBuilder`**

| Directory | Purpose |
|---|---|
| [`../`](../INDEX.md) | ActiveRecord Scaffold Directory |

## Purpose

Driver-specific input control builders for the Active Record scaffold UI. Each class maps database column types to appropriate Prado form controls (text box, checkbox, date picker, etc.) for auto-generated CRUD edit views.

## Classes

- **[`IScaffoldInput`](./IScaffoldInput.md)** — Interface for scaffold input builders. Implemented by `TScaffoldInputBase` and all driver subclasses. Third-party drivers register a class name implementing this interface via the `fxActiveRecordScaffoldInputClass` event.

- **`TScaffoldInputBase`** — Base class implementing `IScaffoldInput`. Static factory `createInputBuilder($record)` selects the correct driver subclass; for unknown drivers raises `fxActiveRecordScaffoldInputClass` on the connection. Subclasses override `createControl()` and `getControlValue()`.

- **`TScaffoldInputCommon`** — Shared logic used across all driver-specific builders: maps generic SQL types (`varchar`, `int`, `bool`, `date`, `text`, etc.) to Prado controls. Extended by each driver subclass.

- **`TMysqlScaffoldInput`** — MySQL-specific mappings: handles `enum`, `set`, `tinyint(1)` as checkbox, MySQL date/datetime/timestamp types.

- **`TPgsqlScaffoldInput`** — PostgreSQL-specific: handles `boolean`, `serial`, `bytea`, array types, PostgreSQL `date`/`timestamp`.

- **`TMssqlScaffoldInput`** — SQL Server-specific: handles `bit`, `uniqueidentifier`, `money`, `nvarchar`, `datetime2`.

- **`TSqliteScaffoldInput`** — SQLite-specific: handles SQLite's loose typing (`INTEGER`, `REAL`, `TEXT`, `BLOB`).

- **`TIbmScaffoldInput`** — IBM DB2-specific: handles DB2 column type conventions (`INTEGER`, `BIGINT`, `DECIMAL`, `TIMESTAMP`, `DATE`, `TIME`, `CHAR`, `VARCHAR`).

## Patterns & Gotchas

- The correct subclass is selected automatically by `TScaffoldInputBase::createInputBuilder($record)` based on the PDO driver name. For unknown drivers the `fxActiveRecordScaffoldInputClass` event fires; the first handler returning a class name wins.
- Subclasses must override `createControl()` to build the input and `getControlValue()` to read back the submitted value.
- The primary input control must use the ID `IScaffoldInput::DEFAULT_ID` (`'scaffold_input'`); without it, no label is generated.
- Column metadata is provided as a `TDbTableColumn` (driver-specific subclass); check `getDbType()` for the raw SQL type string.
