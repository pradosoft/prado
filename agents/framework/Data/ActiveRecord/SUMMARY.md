# SUMMARY.md

Active Record ORM where each AR class maps to one database table; instances represent rows and encapsulate data and persistence logic.

## Classes

- **`TActiveRecord`** — Base class for all AR domain objects; declare `const TABLENAME` and optionally `const COLUMN_MAPPING` and `const RELATIONS`; static finders: `findByPk()`, `findAll()`, `findBySql()`, `count()`; persistence: `save()`, `insert()`, `update()`, `delete()`; lifecycle events: `onBeforeSave`, `onAfterSave`, `onBeforeDelete`, `onAfterDelete`.

- **`TActiveRecordManager`** — Singleton registry providing shared `TDbConnection` and `TDbMetaData` cache.

- **`TActiveRecordConfig`** — `TModule` subclass registered in `application.xml`; properties: `ConnectionID`, `EnableCache`.

- **`TActiveRecordGateway`** — Internal bridge between `TActiveRecord` and `TDbCommandBuilder`; generates INSERT/UPDATE/DELETE/SELECT commands.

- **`TActiveRecordCriteria`** — Extends `TSqlCriteria` with AR-specific options; supports `with()` for eager-loading relations.

- **`TActiveRecordChangeEventParameter`** — Event parameter passed to `onBeforeSave`, `onAfterSave`, etc.; contains record and change type.

- **`TActiveRecordInvalidFinderResult`** — Enum-like; controls what `findByPk()` returns for missing records.
