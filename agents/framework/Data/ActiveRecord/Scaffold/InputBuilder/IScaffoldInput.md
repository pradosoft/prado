# Data/ActiveRecord/Scaffold/InputBuilder/IScaffoldInput

### Directories
[framework](../../../../INDEX.md) / [Data](../../../INDEX.md) / [ActiveRecord](../../INDEX.md) / [Scaffold](../INDEX.md) / [InputBuilder](./INDEX.md) / **`IScaffoldInput`**

## Interface Info
**Location:** `framework/Data/ActiveRecord/Scaffold/InputBuilder/IScaffoldInput.php`
**Namespace:** `Prado\Data\ActiveRecord\Scaffold\InputBuilder`

## Overview

`IScaffoldInput` defines the contract for scaffold input builders — objects that map database column types to appropriate Prado form controls (TTextBox, TCheckBox, TDropDownList, TDatePicker, etc.) for auto-generated CRUD edit views.

All built-in driver-specific input builders implement this interface by extending [`TScaffoldInputBase`](./TScaffoldInputBase.md). Third-party implementations for unsupported drivers may implement it directly.

## Constant

```php
IScaffoldInput::DEFAULT_ID = 'scaffold_input'
```

The ID that every implementation must assign to its primary input control within the scaffold item. `TScaffoldInputBase::createScaffoldInput()` looks for a control with this ID to decide whether to generate a label.

## Methods

```php
$builder->createScaffoldInput($parent, $item, $column, $record): void
    // Build and attach the input control for $column to $item.

$builder->loadScaffoldInput($parent, $item, $column, $record): void
    // Read the submitted control value back into $record.
    // Implementations skip primary-key / sequence columns (read-only on edit).
```

## When This Interface Matters

`TScaffoldInputBase::createInputBuilder($record)` selects the correct builder based on the PDO driver. For drivers with no built-in handler, it raises the **`fxActiveRecordScaffoldInputClass`** global event on the connection. Event handlers must return the fully-qualified class name of a class implementing `IScaffoldInput`. The first returned value wins.

## Implementing for a Custom Driver

```php
class MyDriverScaffoldInput implements IScaffoldInput
{
    public function createScaffoldInput($parent, $item, $column, $record): void
    {
        // Create and attach controls to $item based on $column->getDbType()
    }

    public function loadScaffoldInput($parent, $item, $column, $record): void
    {
        // Read submitted value from $item and write it to $record
    }
}

// Register the class name via the fx event handler, returning MyDriverScaffoldInput::class
```

## See Also

- [TScaffoldInputBase](./TScaffoldInputBase.md) — Abstract base class implementing this interface
- [TScaffoldInputCommon](./TScaffoldInputCommon.md) — Shared logic extended by all built-in subclasses
