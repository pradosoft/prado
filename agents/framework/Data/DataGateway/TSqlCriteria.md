# Data/DataGateway/TSqlCriteria

### Directories
[framework](../../INDEX.md) / [Data](../INDEX.md) / [DataGateway](./INDEX.md) / **`TSqlCriteria`**

## Class Info
**Location:** `framework/Data/DataGateway/TSqlCriteria.php`
**Namespace:** `Prado\Data\DataGateway`

## Overview
`TSqlCriteria` represents search criteria for data gateway finder methods.

## Properties

- `Condition` - SQL WHERE clause
- `Parameters` - Named parameters for the condition
- `OrdersBy` - Ordering specifications
- `Limit` - Maximum number of rows
- `Offset` - Number of rows to skip
- `Select` - SELECT clause (default: `*`)

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

- [TTableGateway](./TTableGateway.md) - Table gateway