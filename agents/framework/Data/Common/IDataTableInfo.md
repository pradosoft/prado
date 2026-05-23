# Data/Common/IDataTableInfo

### Directories
[framework](../../INDEX.md) / [Data](../INDEX.md) / [Common](./INDEX.md) / **`IDataTableInfo`**

## Interface Info
**Location:** `framework/Data/Common/IDataTableInfo.php`
**Namespace:** `Prado\Data\Common`
**Since:** 4.3.3

## Overview

`IDataTableInfo` defines the contract for structured schema metadata about a single database table or view. Implementations are returned by [`IDataMetaData::getTableInfo()`](./IDataMetaData.md) and are consumed by command builders, scaffold generators, and the Active Record layer.

The concrete SQL/PDO implementation is [`TDbTableInfo`](./TDbTableInfo.md). Driver-specific subclasses (e.g. `TMysqlTableInfo`) extend it with driver-specific column and key handling.

## Interface Methods

| Method | Description |
|--------|-------------|
| `getTableName()` | Returns the unquoted table name. |
| `getColumns()` | Returns a `TMap` of column name → column metadata objects. |
| `getPrimaryKeys()` | Returns an array of primary-key column names. |
| `getForeignKeys()` | Returns foreign-key descriptor arrays. |
| `createCommandBuilder($connection)` | Creates an [`IDataCommandBuilder`](./IDataCommandBuilder.md) bound to this table for the given connection. |

## See Also

- [`TDbTableInfo`](./TDbTableInfo.md) — SQL/PDO implementation
- [`IDataMetaData`](./IDataMetaData.md) — factory that returns table info via `getTableInfo()`
- [`IDataCommandBuilder`](./IDataCommandBuilder.md) — created by `createCommandBuilder()`
