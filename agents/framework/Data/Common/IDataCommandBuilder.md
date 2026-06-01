# Data/Common/IDataCommandBuilder

### Directories
[framework](../../INDEX.md) / [Data](../INDEX.md) / [Common](./INDEX.md) / **`IDataCommandBuilder`**

## Interface Info
**Location:** `framework/Data/Common/IDataCommandBuilder.php`
**Namespace:** `Prado\Data\Common`
**Since:** 4.3.3

## Overview

`IDataCommandBuilder` defines the contract for building CRUD commands against a specific database table. Implementations are returned by [`IDataMetaData::createCommandBuilder()`](./IDataMetaData.md) and are consumed by the Active Record, SqlMap, and DataGateway layers.

The concrete SQL/PDO implementation is [`TDbCommandBuilder`](./TDbCommandBuilder.md). Driver-specific subclasses override `applyLimitOffset()` and other dialect-sensitive helpers.

## Interface Methods

| Method | Description |
|--------|-------------|
| `getDbConnection()` | Returns the [`IDataConnection`](../IDataConnection.md) this builder operates against. |
| `getTableInfo()` | Returns the [`IDataTableInfo`](./IDataTableInfo.md) this builder is bound to. |
| `applyLimitOffset($sql, $limit, $offset)` | Applies `LIMIT`/`OFFSET` in the dialect appropriate for the underlying driver. `-1` means no limit/offset. |
| `createFindCommand($where, $params, $ordering, $limit, $offset)` | Creates a `SELECT` command for this table. |
| `createCountCommand($where, $params)` | Creates a `SELECT COUNT(*)` command. |
| `createInsertCommand($data)` | Creates an `INSERT` command; `$data` is a column → value map. |
| `createUpdateCommand($data, $where, $params)` | Creates an `UPDATE` command. |
| `createDeleteCommand($where, $params)` | Creates a `DELETE` command. |

All command methods return an [`IDataCommand`](../IDataCommand.md).

## See Also

- [`TDbCommandBuilder`](./TDbCommandBuilder.md) — SQL/PDO implementation
- [`IDataMetaData`](./IDataMetaData.md) — factory that returns builders via `createCommandBuilder()`
- [`IDataTableInfo`](./IDataTableInfo.md) — table schema bound to this builder
- [`IDataConnection`](../IDataConnection.md) — connection used to execute commands
