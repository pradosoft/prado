# Data/ActiveRecord/Scaffold/InputBuilder/TScaffoldInputBase

### Directories
[framework](../../../../INDEX.md) / [Data](../../../INDEX.md) / [ActiveRecord](../../INDEX.md) / [Scaffold](../INDEX.md) / [InputBuilder](./INDEX.md) / **`TScaffoldInputBase`**

## Class Info
**Location:** `framework/Data/ActiveRecord/Scaffold/InputBuilder/TScaffoldInputBase.php`
**Namespace:** `Prado\Data\ActiveRecord\Scaffold\InputBuilder`
**Implements:** [`IScaffoldInput`](./IScaffoldInput.md)

## Overview

`TScaffoldInputBase` is the base class for scaffold input builders. It maps database column types to appropriate Prado form controls (TTextBox, TCheckBox, TDropDownList, TDatePicker, etc.) for auto-generated ActiveRecord CRUD views, and reads submitted values back into the active record.

## Static Factory

```php
TScaffoldInputBase::createInputBuilder(TActiveRecord $record): IScaffoldInput
```

Selects the correct builder from the active record's database driver:

| Driver string(s) | Class |
|---|---|
| `sqlite`, `sqlite2` | `TSqliteScaffoldInput` |
| `mysql`, `mysqli` | `TMysqlScaffoldInput` |
| `pgsql` | `TPgsqlScaffoldInput` |
| `mssql` | `TMssqlScaffoldInput` |
| `ibm` | `TIbmScaffoldInput` |
| `firebird`, `interbase` | `TFirebirdScaffoldInput` |

**Unknown driver fallback:** raises `fxActiveRecordScaffoldInputClass` on the connection with the driver name string as the parameter. Handlers return a fully-qualified class name implementing [`IScaffoldInput`](./IScaffoldInput.md); the **first returned value** wins. The class is instantiated with `new $class()`. Throws `TConfigurationException('ar_invalid_database_driver')` if no handler responds, or `TConfigurationException('ar_not_input_base')` if the returned class does not implement `IScaffoldInput`.

Built-in driver files are loaded via `require_once` (not PSR-4 autoload) because they live in the same directory without namespace registration.

## Key Methods

```php
// IScaffoldInput contract:
$builder->createScaffoldInput($parent, $item, $column, $record): void
    // Attaches input control(s) to $item for $column. Generates a label if a
    // control with id DEFAULT_ID ('scaffold_input') is found inside $item.

$builder->loadScaffoldInput($parent, $item, $column, $record): void
    // Reads submitted control value and writes it to $record.
    // Skips primary-key / sequence columns (they are not editable on update).

// Protected helpers for subclasses:
$builder->getParent(): mixed                             // parent scaffold config
$builder->getIsEnabled($column, $record): bool          // false for PK+sequence cols
$builder->getRecordPropertyValue($column, $record): mixed   // value with default fallback
$builder->createControl($container, $column, $record)   // override to build controls
$builder->getControlValue($container, $column, $record) // override to read control value
$builder->createControlLabel($label, $column, $record)  // generates human-readable label
```

## Patterns & Gotchas

- **Override `createControl()` and `getControlValue()`** — subclasses must override both. `createControl()` builds and attaches the control; `getControlValue()` reads the submitted value from the same container.
- **`DEFAULT_ID` is required** — the primary input control inside `createControl()` must have its ID set to `IScaffoldInput::DEFAULT_ID` (`'scaffold_input'`). Without it, no label is generated.
- **Third-party drivers** — implement [`IScaffoldInput`](./IScaffoldInput.md) and register via an `fxActiveRecordScaffoldInputClass` handler returning your class name (not an instance). First handler wins.

## See Also

- [IScaffoldInput](./IScaffoldInput.md) — Interface this class implements
- [TScaffoldInputCommon](./TScaffoldInputCommon.md) — Shared SQL-type-to-control mappings
- [TMysqlScaffoldInput](./TMysqlScaffoldInput.md)
- [TPgsqlScaffoldInput](./TPgsqlScaffoldInput.md)
- [TSqliteScaffoldInput](./TSqliteScaffoldInput.md)
- [TMssqlScaffoldInput](./TMssqlScaffoldInput.md)
- [TIbmScaffoldInput](./TIbmScaffoldInput.md)
- [TFirebirdScaffoldInput](./TFirebirdScaffoldInput.md)
