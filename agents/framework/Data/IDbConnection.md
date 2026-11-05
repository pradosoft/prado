# Data/IDbConnection

### Directories
[framework](../INDEX.md) / [Data](./INDEX.md) / **`IDbConnection`**

## Interface Info
**Location:** `framework/Data/IDbConnection.php`
**Namespace:** `Prado\Data`
**Extends:** [`IDataConnection`](./IDataConnection.md)
**Since:** 4.3.3

## Overview

`IDbConnection` extends [`IDataConnection`](./IDataConnection.md) with PDO-specific access by exposing the underlying `PDO` instance directly. It is implemented by [`TDbConnection`](./TDbConnection.md).

## When to Use Each Interface

| Interface | Use when… |
|-----------|-----------|
| `IDataConnection` | Code works with any data store and does not need raw PDO access. |
| `IDbConnection` | Code must call PDO-specific methods (`getPdoInstance()`, etc.) directly. |

Using `IDataConnection` as the type hint keeps code compatible with non-PDO driver implementations (e.g. custom MongoDB or in-memory connections).

## Interface Method

| Method | Description |
|--------|-------------|
| `getPdoInstance()` | Returns the underlying `PDO` object, or `null` if the connection is not yet open. |

All other methods are inherited from [`IDataConnection`](./IDataConnection.md).

## See Also

- [`IDataConnection`](./IDataConnection.md) — parent interface; use this for driver-agnostic code
- [`TDbConnection`](./TDbConnection.md) — concrete implementation of this interface
