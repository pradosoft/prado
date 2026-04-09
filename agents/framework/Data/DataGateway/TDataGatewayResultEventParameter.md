# TDataGatewayResultEventParameter

### Directories

[./](../INDEX.md) > [Data](../../INDEX.md) > [DataGateway](./INDEX.md) > [TDataGatewayResultEventParameter](./TDataGatewayResultEventParameter.md)

**Location:** `framework/Data/DataGateway/TDataGatewayResultEventParameter.php`
**Namespace:** `Prado\Data\DataGateway`

## Overview

`TDataGatewayResultEventParameter` is the event parameter passed to `OnExecuteCommand` handlers. The result can be modified by handlers.

## Properties

- `Command` - The [`TDbCommand`](../TDbCommand.md) that was executed
- `Result` - The result from executing the command (can be modified)

## See Also

- [TDataGatewayCommand](./TDataGatewayCommand.md) - Command builder