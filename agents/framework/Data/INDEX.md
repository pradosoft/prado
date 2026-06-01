# Data/INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Directories
[framework](../INDEX.md) / **`Data`**

| Directory | Purpose |
|---|---|
| [ActiveRecord/](ActiveRecord/INDEX.md) | Stateful ORM |
| [Common/](Common/INDEX.md) | Driver-Specific Implementations |
| [DataGateway/](DataGateway/INDEX.md) | Stateless Table Gateway |
| [SqlMap/](SqlMap/INDEX.md) | XML-based SQL Mapping (iBATIS-style) |

## Purpose

Database access layer for the Prado framework. Provides a PDO wrapper plus three complementary data-access patterns: raw commands, stateless table gateway, stateful active record, and XML-based SQL mapping.

## Top-Level Classes

- **[`TDbConnection`](TDbConnection.md)** — PDO wrapper. Key properties: `ConnectionString` (DSN), `Username`, `Password`, `Charset`, `Attributes`. Methods: `open()`, `close()`, `createCommand($sql)`, `beginTransaction()`, `quoteTableName()`, `quoteColumnName()`. Handles driver-specific charset setup (MySQL, PostgreSQL, SQLite). Raises `OnAfterOpen` event.

- **[`TDbCommand`](TDbCommand.md)** — Wraps a prepared PDO statement. Methods: `execute()`, `query()`, `queryRow()`, `queryColumn()`, `queryScalar()`, `queryAll()`. Parameter binding: `bindParameter()`, `bindValue()`. Lazily prepares statements.

- **[`TDbDataReader`](TDbDataReader.md)** — Iterator over a PDO result set. Methods: `read()`, `readAll()`, `nextResult()`. Implements `Iterator` and `Countable`.

- **[`TDbTransaction`](TDbTransaction.md)** — Transaction wrapper. Methods: `commit()`, `rollBack()`. Property: `Active`.

- **[`TDataSourceConfig`](TDataSourceConfig.md)** — Configuration holder for connection pooling and datasource settings.

- **[`TDbColumnCaseMode`](TDbColumnCaseMode.md)** — Enum: `Preserved`, `LowerCase`, `UpperCase`.

- **[`TDbNullConversionMode`](TDbNullConversionMode.md)** — Enum: `Preserved`, `EmptyStringToNull`, `NullToEmptyString`.

- **[`TDbPropertiesTrait`](TDbPropertiesTrait.md)** (@since 4.3.3) — Reusable trait for classes that own a database connection. Manages `ConnectionID`, `DbConnection`, SQLite auto-creation, `deactivateDbConnection()`, and cached `TTableGateway` instances. Used by `TDataSourceConfig`.

- **[`IDataConnection`](IDataConnection.md)** (@since 4.3.3) — Interface for a data-store connection. Implemented by `TDbConnection`. Defines driver name, active state, command creation, transaction management, and common PDO helpers (`getLastInsertID`, `quoteString`, `getColumnCase`, `getAttribute`, etc.).

- **[`IDbConnection`](IDbConnection.md)** (@since 4.3.3) — Extends `IDataConnection` with `getPdoInstance()` for direct PDO access. Implemented by `TDbConnection`. Use `IDataConnection` for driver-agnostic code; use `IDbConnection` only when raw PDO access is required.

- **[`IDataCommand`](IDataCommand.md)** (@since 4.3.3) — Interface for a data-store command. Implemented by `TDbCommand`. Includes `bindValue()` and `bindParameter()` for parameter binding.

- **[`IDataReader`](IDataReader.md)** (@since 4.3.3) — Interface for a forward-only data result reader. Implemented by `TDbDataReader`.

- **[`IDataTransaction`](IDataTransaction.md)** (@since 4.3.3) — Interface for a data-store transaction. Implemented by `TDbTransaction`.

- **[`TDbDriver`](TDbDriver.md)** (@since 4.3.3) — Static enumeration of all PDO driver name strings (`DRIVER_MYSQL`, `DRIVER_PGSQL`, `DRIVER_SQLITE`, etc.) and non-PDO PHP extension aliases (`EXTENSION_MYSQLI`, `EXTENSION_MSSQL`). Replaces all raw string literals throughout the framework; extends `TEnumerable`.

- **[`TDataCharset`](TDataCharset.md)** (@since 4.3.3) — Static enumeration of IANA charset names (`UTF8`, `Latin1`, `Win1250`, etc.) for use with `TDbConnection::setCharset()`. Extends `TEnumerable`.

## Subdirectories

### `ActiveRecord/` — Stateful ORM

Domain objects that represent database rows. Each class maps to one table.

- **[`TActiveRecord`](TActiveRecord.md)** — Base class. Define `const TABLENAME` and optionally `const COLUMN_MAPPING`. Key methods:
  - Finders: `findByPk()`, `findAll()`, `findBySql()`, `findAllBySql()`, `count()`
  - Persistence: `save()`, `insert()`, `update()`, `delete()`
  - Relations: `hasMany()`, `belongsTo()`, `hasOne()`, `manyMany()` — lazily loaded
  - Lifecycle events: `onBeforeSave`, `onAfterSave`, `onBeforeDelete`, `onAfterDelete`
- **[`TActiveRecordManager`](ActiveRecord/TActiveRecordManager.md)** — Singleton; configure via `TActiveRecordConfig` in `application.xml`.
- **[`TActiveRecordGateway`](ActiveRecord/TActiveRecordGateway.md)** — Internal command builder for AR operations.
- **`Relations/`** — Relationship implementations (HasMany, BelongsTo, HasManyBelongsToMany, etc.).
- **`Scaffold/`** — Auto-generated CRUD UI: [`TScaffoldEditView`](ActiveRecord/Scaffold/TScaffoldEditView.md), [`TScaffoldListView`](ActiveRecord/Scaffold/TScaffoldListView.md), `InputBuilder`.
- **`Exceptions/`** — AR-specific exception classes.

### `DataGateway/` — Stateless Table Gateway

Lightweight, stateless access to a single table. No object identity tracking.

- **[`TTableGateway`](TTableGateway.md)** — Gateway to one table. Methods: `findByPk()`, `findAll()`, `findBySql()`, `findCount()`, `insert()`, `update()`, `updateByPk()`, `delete()`, `deleteByPk()`. Returns [`TDbDataReader`](TDbDataReader.md) for multi-row results. Raises `OnCreateCommand` and `OnExecuteCommand` events.
- **[`TDataGatewayCommand`](DataGateway/TDataGatewayCommand.md)** — Internal command builder; raises `OnCreateCommand`/`OnExecuteCommand` on the parent gateway.
- **[`TSqlCriteria`](DataGateway/TSqlCriteria.md)** — Helper for WHERE clauses and parameter binding. Properties: `Condition`, `Parameters`, `OrdersBy`, `Limit`, `Offset`, `Select`. Constructor shorthand: `new TSqlCriteria('col = :v', [':v' => $val])`.
- **[`TDataGatewayEventParameter`](DataGateway/TDataGatewayEventParameter.md)** / **[`TDataGatewayResultEventParameter`](DataGateway/TDataGatewayResultEventParameter.md)** — Event parameters for `OnCreateCommand` / `OnExecuteCommand`.

### `SqlMap/` — XML-based SQL Mapping (iBATIS-style)

Separates SQL from code via external XML mapping files.

- **[`TSqlMapManager`](SqlMap/TSqlMapManager.md)** — Registry and manager; load via `TSqlMapConfig`.
- **[`TSqlMapGateway`](SqlMap/TSqlMapGateway.md)** — Facade: `queryForObject()`, `queryForList()`, `queryForMap()`, `insert()`, `update()`, `delete()`.
- **`Configuration/`** — XML parsing: `TSqlMapStatement`, `TParameterMap`, `TResultMap`, `TResultProperty`, cache models, dynamic SQL.
- **`DataMapper/`** — Runtime execution engine; paging support via `TSqlMapPagedList`.
- **`Statements/`** — Statement type classes (Select, Insert, Update, Delete).

### `Common/` — Driver-Specific Implementations

Subdirs: `Firebird/`, `Ibm/`, `Mssql/`, `Mysql/`, `Oracle/`, `Pgsql/`, `Sqlite/`

Each subdir contains four classes: `T{Driver}MetaData`, `T{Driver}CommandBuilder`, `T{Driver}TableInfo`, `T{Driver}TableColumn`.

- **[`TDbMetaData`](Common/TDbMetaData.md)** — Abstract base; static factory `TDbMetaData::getInstance($conn)` selects the correct driver subclass automatically from the PDO driver name. Method: `getTableInfo($table)` (cached).
- **[`TDbCommandBuilder`](Common/TDbCommandBuilder.md)** — Abstract base query builder. Methods: `createFindCommand()`, `createInsertCommand()`, `createUpdateCommand()`, `createDeleteCommand()`, `applyLimitOffset()`.
- **[`TDbTableInfo`](Common/TDbTableInfo.md)** — Table schema: `TableName`, `Columns`, `PrimaryKeys`, `ForeignKeys`. Method: `createCommandBuilder($conn)`.
- **[`TDbTableColumn`](Common/TDbTableColumn.md)** — Column schema: `ColumnName`, `DbType`, `PhpType`, `IsPrimaryKey`, `AllowNull`, `DefaultValue`, `getAutoIncrement()`.

## Patterns & Conventions

- **Always use parameter binding** — never interpolate user input into SQL strings.
- **Choose the right pattern:**
  - [`TActiveRecord`](TActiveRecord.md) for stateful ORM with lazy relationships.
  - [`TTableGateway`](TTableGateway.md) for lightweight stateless access.
  - `TSqlMap` when you need full SQL control via external XML.
- **DSN format:** `mysql:host=localhost;dbname=mydb` (standard PDO DSN).
- **`COLUMN_MAPPING`** in AR classes maps logical property names to physical column names.
- **`TSqlCriteria`** is the standard way to pass WHERE/ORDER/LIMIT to gateway methods.
