# Data/Common/SUMMARY.md

Driver-agnostic base classes for database schema introspection and SQL query building; each database driver has a subdirectory extending these abstractions.

## Classes

- **`TDbMetaData`** — Abstract base for schema introspection; method: `getTableInfo($tableName)` returns `TDbTableInfo` (cached); static factory: `createMetaData($connection)` selects driver subclass from PDO driver name.

- **`TDbCommandBuilder`** — Base query builder; methods: `createFindCommand()`, `createInsertCommand()`, `createUpdateCommand()`, `createDeleteCommand()`, `createCountCommand()`, `applyLimitOffset()`.

- **`TDbTableInfo`** — Schema data for one table; properties: `TableName`, `Columns`, `PrimaryKeys`, `ForeignKeys`; method: `createCommandBuilder($connection)`.

- **`TDbTableColumn`** — Schema data for one column; properties: `ColumnName`, `ColumnId`, `DbType`, `PhpType`, `IsPrimaryKey`, `IsExcluded`, `AllowNull`, `DefaultValue`, `SequenceName`; method: `getAutoIncrement()`.
