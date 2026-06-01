# Data/TDbDriver

### Directories
[framework](../INDEX.md) / [Data](./INDEX.md) / **`TDbDriver`**

## Class Info
**Location:** `framework/Data/TDbDriver.php`
**Namespace:** `Prado\Data`
**Extends:** [`TEnumerable`](../TEnumerable.md)
**Since:** 4.3.3

## Overview

`TDbDriver` is a static enumeration class that centralises all PDO database driver name strings used throughout the framework. Every place that previously compared against a raw string literal such as `'mysql'` or `'pgsql'` now references a constant from this class, eliminating spelling errors (e.g. the historical `'postgresql'` typo).

Because the class extends `TEnumerable`, `TDbDriver::getValues()` returns every constant value for iteration.

## Constants

### Supported PDO Drivers

| Constant | Value | Description |
|----------|-------|-------------|
| `DRIVER_MYSQL` | `'mysql'` | MySQL / MariaDB |
| `DRIVER_PGSQL` | `'pgsql'` | PostgreSQL (charset settable after connect) |
| `DRIVER_SQLITE` | `'sqlite'` | SQLite 3 (UTF-8 / UTF-16; charset only before tables exist) |
| `DRIVER_SQLITE2` | `'sqlite2'` | SQLite 2 |
| `DRIVER_SQLSRV` | `'sqlsrv'` | Microsoft SQL Server (Windows PDO driver) |
| `DRIVER_DBLIB` | `'dblib'` | SQL Server / Sybase via FreeTDS (Linux) |
| `DRIVER_OCI` | `'oci'` | Oracle |
| `DRIVER_IBM` | `'ibm'` | IBM DB2 (no runtime charset switching) |
| `DRIVER_FIREBIRD` | `'firebird'` | Firebird |
| `DRIVER_INTERBASE` | `'interbase'` | Interbase (mapped to same handlers as Firebird) |
| `DRIVER_MONGO` | `'mongo'` | MongoDB (external extension) |

### Unsupported Drivers (listed for reference)

| Constant | Value |
|----------|-------|
| `DRIVER_ODBC` | `'odbc'` |
| `DRIVER_CUBRID` | `'cubrid'` |
| `DRIVER_INFORMIX` | `'informix'` |

### Non-PDO PHP Extensions

| Constant | Value | Note |
|----------|-------|------|
| `EXTENSION_MYSQLI` | `'mysqli'` | Legacy `mysqli` PHP extension; mapped to MySQL handlers |
| `EXTENSION_MSSQL` | `'mssql'` | Legacy `mssql` PHP extension; mapped to MSSQL handlers |

## Usage

```php
use Prado\Data\TDbDriver;

// Comparison in a switch:
switch ($conn->getDriverName()) {
    case TDbDriver::DRIVER_MYSQL:
        // ...
    case TDbDriver::DRIVER_PGSQL:
        // ...
}

// Enumerate all driver strings:
foreach (TDbDriver::getValues() as $driver) {
    echo $driver . "\n";
}
```

## See Also

- [`TDbConnection`](./TDbConnection.md) — uses these constants for driver-specific behaviour
- [`TDbMetaData`](../Data/Common/TDbMetaData.md) — factory switch uses these constants
- [`TDataCharset`](./TDataCharset.md) — companion enumeration for IANA charset names
