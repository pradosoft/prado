# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Purpose

Database access layer for the Prado framework. Provides a PDO wrapper plus three complementary data-access patterns: raw commands, stateless table gateway, stateful active record, and XML-based SQL mapping.

## Top-Level Classes

- **`TDbConnection`** — PDO wrapper. Key properties: `ConnectionString` (DSN), `Username`, `Password`, `Charset`, `Attributes`. Methods: `open()`, `close()`, `createCommand($sql)`, `beginTransaction()`, `quoteTableName()`, `quoteColumnName()`. Handles driver-specific charset setup (MySQL, PostgreSQL, SQLite). Raises `OnAfterOpen` event.

- **`TDbCommand`** — Wraps a prepared PDO statement. Methods: `execute()`, `query()`, `queryRow()`, `queryColumn()`, `queryScalar()`. Parameter binding: `bindParameter()`, `bindValue()`. Lazily prepares statements.

- **`TDbDataReader`** — Iterator over a PDO result set. Methods: `read()`, `readAll()`, `nextResult()`. Implements `Iterator` and `Countable`.

- **`TDbTransaction`** — Transaction wrapper. Methods: `commit()`, `rollBack()`. Property: `Active`.

- **`TDataSourceConfig`** — Configuration holder for connection pooling and datasource settings.

- **`TDbColumnCaseMode`** — Enum: `Preserved`, `LowerCase`, `UpperCase`.

- **`TDbNullConversionMode`** — Enum: `Preserved`, `EmptyStringToNull`, `NullToEmptyString`.

## Subdirectories

### `ActiveRecord/` — Stateful ORM

Domain objects that represent database rows. Each class maps to one table.

- **`TActiveRecord`** — Base class. Define `const TABLENAME` and optionally `const COLUMN_MAPPING`. Key methods:
  - Finders: `findByPk()`, `findAll()`, `findBySql()`, `findAllBySql()`, `count()`
  - Persistence: `save()`, `insert()`, `update()`, `delete()`
  - Relations: `hasMany()`, `belongsTo()`, `hasOne()`, `manyMany()` — lazily loaded
  - Lifecycle events: `onBeforeSave`, `onAfterSave`, `onBeforeDelete`, `onAfterDelete`
- **`TActiveRecordManager`** — Singleton; configure via `TActiveRecordConfig` in `application.xml`.
- **`TActiveRecordGateway`** — Internal command builder for AR operations.
- **`Relations/`** — Relationship implementations (HasMany, BelongsTo, HasManyBelongsToMany, etc.).
- **`Scaffold/`** — Auto-generated CRUD UI: `TScaffoldEditView`, `TScaffoldListView`, `InputBuilder`.
- **`Exceptions/`** — AR-specific exception classes.

### `DataGateway/` — Stateless Table Gateway

Lightweight, stateless access to a single table. No object identity tracking.

- **`TTableGateway`** — Gateway to one table. Methods: `findByPk()`, `findAll()`, `findBySql()`, `findCount()`, `insert()`, `update()`, `updateByPk()`, `delete()`, `deleteByPk()`. Returns `TDbDataReader` for multi-row results. Raises `OnCreateCommand` and `OnExecuteCommand` events.
- **`TDataGatewayCommand`** — Internal command builder; raises `OnCreateCommand`/`OnExecuteCommand` on the parent gateway.
- **`TSqlCriteria`** — Helper for WHERE clauses and parameter binding. Properties: `Condition`, `Parameters`, `OrdersBy`, `Limit`, `Offset`, `Select`. Constructor shorthand: `new TSqlCriteria('col = :v', [':v' => $val])`.
- **`TDataGatewayEventParameter`** / **`TDataGatewayResultEventParameter`** — Event parameters for `OnCreateCommand` / `OnExecuteCommand`.

### `SqlMap/` — XML-based SQL Mapping (iBATIS-style)

Separates SQL from code via external XML mapping files.

- **`TSqlMapManager`** — Registry and manager; load via `TSqlMapConfig`.
- **`TSqlMapGateway`** — Facade: `queryForObject()`, `queryForList()`, `queryForMap()`, `insert()`, `update()`, `delete()`.
- **`Configuration/`** — XML parsing: `TSqlMapStatement`, `TParameterMap`, `TResultMap`, `TResultProperty`, cache models, dynamic SQL.
- **`DataMapper/`** — Runtime execution engine; paging support via `TSqlMapPagedList`.
- **`Statements/`** — Statement type classes (Select, Insert, Update, Delete).

### `Common/` — Driver-Specific Implementations

Subdirs: `Mssql/`, `Mysql/`, `Oracle/`, `Pgsql/`, `Sqlite/`

Each subdir contains four classes: `T{Driver}MetaData`, `T{Driver}CommandBuilder`, `T{Driver}TableInfo`, `T{Driver}TableColumn`.

- **`TDbMetaData`** — Abstract base; static factory `TDbMetaData::getInstance($conn)` selects the correct driver subclass automatically from the PDO driver name. Method: `getTableInfo($table)` (cached).
- **`TDbCommandBuilder`** — Abstract base query builder. Methods: `createFindCommand()`, `createInsertCommand()`, `createUpdateCommand()`, `createDeleteCommand()`, `applyLimitOffset()`.
- **`TDbTableInfo`** — Table schema: `TableName`, `Columns`, `PrimaryKeys`, `ForeignKeys`. Method: `createCommandBuilder($conn)`.
- **`TDbTableColumn`** — Column schema: `ColumnName`, `DbType`, `PhpType`, `IsPrimaryKey`, `AllowNull`, `DefaultValue`, `getAutoIncrement()`.

## Patterns & Conventions

- **Always use parameter binding** — never interpolate user input into SQL strings.
- **Choose the right pattern:**
  - `TActiveRecord` for stateful ORM with lazy relationships.
  - `TTableGateway` for lightweight stateless access.
  - `TSqlMap` when you need full SQL control via external XML.
- **DSN format:** `mysql:host=localhost;dbname=mydb` (standard PDO DSN).
- **`COLUMN_MAPPING`** in AR classes maps logical property names to physical column names.
- **`TSqlCriteria`** is the standard way to pass WHERE/ORDER/LIMIT to gateway methods.
