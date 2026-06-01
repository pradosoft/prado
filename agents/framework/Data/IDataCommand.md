# Data/IDataCommand

### Directories
[framework](../INDEX.md) / [Data](./INDEX.md) / **`IDataCommand`**

## Interface Info
**Location:** `framework/Data/IDataCommand.php`
**Namespace:** `Prado\Data`
**Since:** 4.3.3

## Overview
`IDataCommand` defines the common interface for a data-store command. The concrete implementation for SQL/PDO databases is [`TDbCommand`](./TDbCommand.md).

## Interface Methods

| Method | Description |
|--------|-------------|
| `getConnection()` | Returns the [`IDataConnection`](./IDataConnection.md) that owns this command. |
| `execute()` | Executes a non-query operation (INSERT/UPDATE/DELETE); returns number of affected rows. |
| `query()` | Executes a query; returns an [`IDataReader`](./IDataReader.md). |
| `queryRow($fetchAssociative = true)` | Returns the first row as an array, or `false`. |
| `queryScalar()` | Returns the scalar value of the first column in the first row, or `false`. |
| `queryColumn()` | Returns all values of the first column as an array. |
| `queryAll()` | Returns all rows as an array. |
| `bindValue($name, $value, $dataType = null)` | Binds a value to a named (`:name`) or 1-based positional parameter. `$dataType` is a PDO constant; `null` infers from value. |
| `bindParameter($name, &$value, $dataType = null, $length = null)` | Binds a PHP variable by reference; the variable is read at execute time. `$length` is required by some drivers for OUTPUT parameters. |

## See Also

- [`IDataConnection`](./IDataConnection.md) — connection interface
- [`IDataReader`](./IDataReader.md) — reader interface
- [`TDbCommand`](./TDbCommand.md) — SQL/PDO implementation
