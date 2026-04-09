# TActiveRecordConfigurationException

### Directories

[./](../../INDEX.md) > [Data](../../INDEX.md) > [ActiveRecord](../INDEX.md) > [Exceptions](./INDEX.md) > [TActiveRecordConfigurationException](./TActiveRecordConfigurationException.md)

`Prado\Data\ActiveRecord\Exceptions\TActiveRecordConfigurationException`

Exception thrown when Active Record is improperly configured.

Inherits from `TActiveRecordException`.

## Description

`TActiveRecordConfigurationException` is thrown when Active Record is improperly configured. This includes missing `TActiveRecordManager`, invalid `TABLENAME` in an Active Record class, misconfigured `COLUMN_MAPPING`, or other configuration-related errors.

## Common Causes

- Missing [`TActiveRecordManager`](../TActiveRecordManager.md) in application configuration
- Invalid or missing `TABLENAME` constant in an Active Record class
- Misconfigured `COLUMN_MAPPING` property
- Invalid connection ID referenced in [`TActiveRecordConfig`](../TActiveRecordConfig.md)

## See Also

- [TActiveRecordException](./TActiveRecordException.md)
- `TActiveRecordManager`
- `TActiveRecordConfig`

## Category

ActiveRecord Exceptions
