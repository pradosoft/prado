# TDbMetaData

### Directories

[./](../INDEX.md) > [Data](../../INDEX.md) > [Common](./INDEX.md) > [TDbMetaData](./TDbMetaData.md)

**Location:** `framework/Data/Common/TDbMetaData.php`
**Namespace:** `Prado\Data\Common`

## Overview

`TDbMetaData` is the abstract base class for retrieving metadata information (tables, columns) from a database connection.

## Key Methods

```php
// Get table information (cached)
$tableInfo = TDbMetaData::getInstance($conn)->getTableInfo('users');

// List all tables
$tables = $metaData->findTableNames();
```

## Subclasses

Each database driver has a specific subclass:
- [`TMysqlMetaData`](./Mysql/TMysqlMetaData.md) - MySQL/MariaDB
- [`TPgsqlMetaData`](./Pgsql/TPgsqlMetaData.md) - PostgreSQL
- [`TSqliteMetaData`](./Sqlite/TSqliteMetaData.md) - SQLite
- [`TMssqlMetaData`](./Mssql/TMssqlMetaData.md) - SQL Server
- [`TOracleMetaData`](./Oracle/TOracleMetaData.md) - Oracle

## See Also

- [TDbTableInfo](./TDbTableInfo.md) - Table metadata container
- [TDbTableColumn](./TDbTableColumn.md) - Column metadata