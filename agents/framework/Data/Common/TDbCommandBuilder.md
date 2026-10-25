# Data/Common/TDbCommandBuilder

### Directories
[framework](../../INDEX.md) / [Data](../INDEX.md) / [Common](./INDEX.md) / **`TDbCommandBuilder`**

## Class Info
**Location:** `framework/Data/Common/TDbCommandBuilder.php`
**Namespace:** `Prado\Data\Common`
**Implements:** [`IDataCommandBuilder`](./IDataCommandBuilder.md)

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

## Search Expression

`getSearchExpression($fields, $keywords)` ORs one `LIKE` condition group per column; each group ANDs one condition per whitespace-separated keyword. `getSearchPattern($word)` quotes `%word%` and appends `ESCAPE '\'` (`SEARCH_ESCAPE_CHAR`), after `escapeSearchWord()` prefixes `%`, `_`, and `\` in the keyword with the escape character, so a keyword's wildcards match literally on every driver. `TPgsqlCommandBuilder` uses the same pattern with `ILIKE`.

## Properties

- `DbConnection` - The database connection
- `TableInfo` - The [`TDbTableInfo`](./TDbTableInfo.md) for the table

## See Also

- [TDbTableInfo](./TDbTableInfo.md) - Table metadata
- [TDbConnection](../TDbConnection.md) - Database connection