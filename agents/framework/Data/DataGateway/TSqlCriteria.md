# Data/DataGateway/TSqlCriteria

### Directories
[framework](../../INDEX.md) / [Data](../INDEX.md) / [DataGateway](./INDEX.md) / **`TSqlCriteria`**

## Class Info
**Location:** `framework/Data/DataGateway/TSqlCriteria.php`
**Namespace:** `Prado\Data\DataGateway`

## Overview
`TSqlCriteria` represents search criteria for data gateway finder methods.

## Properties

- `Condition` - SQL WHERE clause; null or `''` resets it, and `'0'` is a condition that matches no row
- `Parameters` - Named parameters for the condition; an array or an `ArrayAccess`, else `TInvalidDataTypeException` (`sqlcriteria_parameters_invalid`)
- `OrdersBy` - Ordering specifications
- `Limit` - Maximum number of rows
- `Offset` - Number of rows to skip
- `Select` - SELECT clause (default: `*`)

## Clauses in the Condition

A trailing `ORDER BY`, `LIMIT {[offset,] row_count | row_count OFFSET offset}`, or `OFFSET` in `Condition` moves into `OrdersBy`, `Limit`, and `Offset`. Text inside quotes (`'...'`, `"..."`, `` `...` ``) or parentheses stays in the condition, so a string literal or subquery holding these words is unchanged.

## Usage

```php
$criteria = new TSqlCriteria();
$criteria->Condition = 'username = :name AND active = :active';
$criteria->Parameters[':name'] = 'admin';
$criteria->Parameters[':active'] = 1;
$criteria->OrdersBy['created'] = 'desc';
$criteria->Limit = 10;
```

## See Also

- [TTableGateway](../TTableGateway.md) - Table gateway