# Data/IDataReader

### Directories
[framework](../INDEX.md) / [Data](./INDEX.md) / **`IDataReader`**

## Interface Info
**Location:** `framework/Data/IDataReader.php`
**Namespace:** `Prado\Data`
**Since:** 4.3.3

## Overview
`IDataReader` defines the interface for a forward-only data result reader. It extends PHP's `\Iterator` interface. The concrete implementation for SQL/PDO databases is [`TDbDataReader`](./TDbDataReader.md).

## Interface Methods

| Method | Description |
|--------|-------------|
| `read()` | Reads the next row; returns an associative array or `false` when exhausted. |
| `readAll()` | Reads all remaining rows into an array. |
| `close()` | Closes the reader and releases held resources. |
| `getIsClosed()` | Returns `true` if the reader has been closed. |
| `getRowCount()` | Returns the number of rows in the result set (accuracy varies by driver). |

Inherits standard `Iterator` methods: `current()`, `key()`, `next()`, `rewind()`, `valid()`.

## See Also

- [IDataCommand](./IDataCommand.md) - Command interface (produces readers via `query()`)
- [TDbDataReader](./TDbDataReader.md) - SQL/PDO implementation
