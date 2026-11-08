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

## Index Condition

`getIndexCondition($fields, $values, $table = null)` builds the condition that `createIndexCriteria()`, `createIndexedFindCommand()`, and `TDataGatewayCommand::getIndexKeyCondition()` use for key lookups. `$table` defaults to the builder's table. The values of each row pair with `$fields` by position.

| Input | Condition |
|---|---|
| no values | `1=0` |
| one field | `t.a IN ('1', '2')` |
| several fields, one row | `(t.a = '1' AND t.b = 'x')` |
| several fields, several rows | `((t.a = '1' AND t.b = 'x') OR (t.a = '2' AND t.b = 'y'))` |

- `1=0` is the false predicate; Oracle, SQL Server, Firebird, and DB2 reject a bare `FALSE`.
- Several fields use equalities. SQL Server and DB2 reject the row-value `(t.a, t.b) IN ((...))` form, and MySQL does not use an index for it in `DELETE` and `UPDATE` (issue #129).
- The outer parentheses keep the `OR` intact when a caller appends the condition after `AND`.

## Properties

- `DbConnection` - The database connection
- `TableInfo` - The [`TDbTableInfo`](./TDbTableInfo.md) for the table

## See Also

- [TDbTableInfo](./TDbTableInfo.md) - Table metadata
- [TDbConnection](../TDbConnection.md) - Database connection