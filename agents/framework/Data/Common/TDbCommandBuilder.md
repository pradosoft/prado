# Data/Common/TDbCommandBuilder

### Directories
[framework](../../INDEX.md) / [Data](../INDEX.md) / [Common](./INDEX.md) / **`TDbCommandBuilder`**

## Class Info
**Location:** `framework/Data/Common/TDbCommandBuilder.php`
**Namespace:** `Prado\Data\Common`

## Overview
`TDbCommandBuilder` provides methods to create SQL query commands for a specific table.

## Key Methods

```php
$builder = new TDbCommandBuilder($connection, $tableInfo);

// SELECT commands
$cmd = $builder->createFindCommand($where, $params, $ordering, $limit, $offset);
$cmd = $builder->createCountCommand($where, $params);

// INSERT/UPDATE/DELETE commands
$cmd = $builder->createInsertCommand($data);
$cmd = $builder->createUpdateCommand($data, $where, $params);
$cmd = $builder->createDeleteCommand($where, $params);

// Get last insert ID
$id = $builder->getLastInsertID();
```

## Properties

- `DbConnection` - The database connection
- `TableInfo` - The [`TDbTableInfo`](./TDbTableInfo.md) for the table

## See Also

- [TDbTableInfo](./TDbTableInfo.md) - Table metadata
- [TDbConnection](../TDbConnection.md) - Database connection