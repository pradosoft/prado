# TScaffoldInputBase

### Directories

[./](../../INDEX.md) > [Data](../../INDEX.md) > [ActiveRecord](../INDEX.md) > [Scaffold](../INDEX.md) > [InputBuilder](./INDEX.md) > [TScaffoldInputBase](./TScaffoldInputBase.md)

`Prado\Data\ActiveRecord\Scaffold\InputBuilder\TScaffoldInputBase`

Abstract base class for scaffold input builders.

## Description

`TScaffoldInputBase` is the abstract base class for scaffold input builders that create appropriate UI controls based on database column types. Each database driver has its own subclass that maps column types to Prado control types.

## Factory Method

### `createInputBuilder($record)`

Creates the appropriate input builder based on the database driver.

```php
$builder = TScaffoldInputBase::createInputBuilder($record);
```

**Supported Drivers:**
- `sqlite`, `sqlite2` - `TSqliteScaffoldInput`
- `mysqli`, `mysql` - `TMysqlScaffoldInput`
- `pgsql` - `TPgsqlScaffoldInput`
- `mssql` - `TMssqlScaffoldInput`
- `ibm` - `TIbmScaffoldInput`
- `firebird`, `interbase` - `TFirebirdScaffoldInput`

## Key Methods

### `createScaffoldInput($parent, $item, $column, $record)`

Creates the scaffold input control for a column and binds it to the repeater item.

### `loadScaffoldInput($parent, $item, $column, $record)`

Loads the scaffold input value back into the record.

### `getIsEnabled($column, $record)`

Determines if a column control should be enabled (disabled for PK when editing).

### `getRecordPropertyValue($column, $record)`

Gets the record property value, applying default value if null.

## See Also

- [TScaffoldInputCommon](./TScaffoldInputCommon.md)
- [TMysqlScaffoldInput](./TMysqlScaffoldInput.md)
- [TPgsqlScaffoldInput](./TPgsqlScaffoldInput.md)
- [TSqliteScaffoldInput](./TSqliteScaffoldInput.md)
- [TMssqlScaffoldInput](./TMssqlScaffoldInput.md)
- [TIbmScaffoldInput](./TIbmScaffoldInput.md)
- [TFirebirdScaffoldInput](./TFirebirdScaffoldInput.md)

## Category

ActiveRecord Scaffold InputBuilder
