# Data/IDataTransaction

### Directories
[framework](../INDEX.md) / [Data](./INDEX.md) / **`IDataTransaction`**

## Interface Info
**Location:** `framework/Data/IDataTransaction.php`
**Namespace:** `Prado\Data`
**Since:** 4.3.3

## Overview
`IDataTransaction` defines the interface for a data-store transaction. The concrete implementation for SQL/PDO databases is [`TDbTransaction`](./TDbTransaction.md).

## Interface Methods

| Method | Description |
|--------|-------------|
| `getConnection()` | Returns the [`IDataConnection`](./IDataConnection.md) this transaction belongs to. |
| `getActive()` | Returns `true` if the transaction is currently open. |
| `commit()` | Commits the transaction. The transaction becomes inactive; call `beginTransaction()` on this object or on the connection to start another work unit. |
| `rollback()` | Rolls back (aborts) the transaction. The transaction becomes inactive; call `beginTransaction()` to start another work unit. |

## See Also

- [IDataConnection](./IDataConnection.md) - Connection interface
- [TDbTransaction](./TDbTransaction.md) - SQL/PDO implementation
