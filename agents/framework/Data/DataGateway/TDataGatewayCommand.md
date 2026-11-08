# Data/DataGateway/TDataGatewayCommand

### Directories
[framework](../../INDEX.md) / [Data](../INDEX.md) / [DataGateway](./INDEX.md) / **`TDataGatewayCommand`**

## Class Info
**Location:** `framework/Data/DataGateway/TDataGatewayCommand.php`
**Namespace:** `Prado\Data\DataGateway`

## Overview
`TDataGatewayCommand` is the command builder and executor for [`TTableGateway`](../TTableGateway.md). The `pradosoft/prado-activerecord` package's `TActiveRecordGateway` uses it the same way.

## Index Conditions

`findAllByIndex()`, `findAllByPks()`, and `deleteByPk()` match keys through the builder's `createIndexCriteria()`. The deprecated `getIndexKeyCondition($table, $fields, $values)` returns the same condition for `$table`. [`TDbCommandBuilder::getIndexCondition()`](../Common/TDbCommandBuilder.md#index-condition) builds it: `t.a IN (...)` for one column, an `OR` of `AND`-joined equalities for several, and `1=0` for no values.

## Events

- `OnCreateCommand` - Raised after command is built, before execution. Handlers receive `TDataGatewayEventParameter`.
- `OnExecuteCommand` - Raised after command is executed. Handlers receive `TDataGatewayResultEventParameter`.

## See Also

- [TTableGateway](../TTableGateway.md) - Table gateway
- [TDataGatewayEventParameter](./TDataGatewayEventParameter.md) - Event parameter
- [TSqlCriteria](./TSqlCriteria.md) - Query criteria