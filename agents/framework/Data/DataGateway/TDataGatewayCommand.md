# Data/DataGateway/TDataGatewayCommand

### Directories
[framework](../../INDEX.md) / [Data](../INDEX.md) / [DataGateway](./INDEX.md) / **`TDataGatewayCommand`**

## Class Info
**Location:** `framework/Data/DataGateway/TDataGatewayCommand.php`
**Namespace:** `Prado\Data\DataGateway`

## Overview
`TDataGatewayCommand` is the command builder and executor for [`TTableGateway`](../TTableGateway.md). The `pradosoft/prado-activerecord` package's `TActiveRecordGateway` uses it the same way.

## Index Conditions

`getIndexKeyCondition($table, $fields, $values)` builds `(table.col1, table.col2) IN ((v1, v2), ...)` for `findAllByIndex()`, `findAllByPk()`, and `deleteByPk()`. An empty value list gives `1=0`, which every driver accepts as a false predicate (a bare `FALSE` is not one in Oracle, SQL Server, Firebird, or DB2).

## Events

- `OnCreateCommand` - Raised after command is built, before execution. Handlers receive `TDataGatewayEventParameter`.
- `OnExecuteCommand` - Raised after command is executed. Handlers receive `TDataGatewayResultEventParameter`.

## See Also

- [TTableGateway](../TTableGateway.md) - Table gateway
- [TDataGatewayEventParameter](./TDataGatewayEventParameter.md) - Event parameter
- [TSqlCriteria](./TSqlCriteria.md) - Query criteria