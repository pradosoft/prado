# Data/DataGateway/TDataGatewayCommand

### Directories
[framework](../../INDEX.md) / [Data](../INDEX.md) / [DataGateway](./INDEX.md) / **`TDataGatewayCommand`**

## Class Info
**Location:** `framework/Data/DataGateway/TDataGatewayCommand.php`
**Namespace:** `Prado\Data\DataGateway`

## Overview
`TDataGatewayCommand` is the command builder and executor for [`TTableGateway`](./TTableGateway.md) and [`TActiveRecordGateway`](../ActiveRecord/TActiveRecordGateway.md).

## Events

- `OnCreateCommand` - Raised after command is built, before execution. Handlers receive `TDataGatewayEventParameter`.
- `OnExecuteCommand` - Raised after command is executed. Handlers receive `TDataGatewayResultEventParameter`.

## See Also

- [TTableGateway](./TTableGateway.md) - Table gateway
- [TDataGatewayEventParameter](./TDataGatewayEventParameter.md) - Event parameter
- [TSqlCriteria](./TSqlCriteria.md) - Query criteria