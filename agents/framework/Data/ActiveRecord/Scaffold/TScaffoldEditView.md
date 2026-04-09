# TScaffoldEditView

### Directories

[./](../../INDEX.md) > [Data](../../INDEX.md) > [ActiveRecord](../INDEX.md) > [Scaffold](./INDEX.md) > [TScaffoldEditView](./TScaffoldEditView.md)

`Prado\Data\ActiveRecord\Scaffold\TScaffoldEditView`

Template control for editing or creating an Active Record instance.

Inherits from [`TScaffoldBase`](./TScaffoldBase.md).

## Description

`TScaffoldEditView` provides a form interface for editing an existing Active Record or creating a new one. The default editor input controls are created based on column types (text boxes, drop-down lists, date pickers, etc.).

For custom editing interfaces, implement `IScaffoldEditRenderer` and set it via the `EditRenderer` property.

## Usage

```php
<com:TScaffoldEditView ID="editView" RecordClass="UserRecord" />
```

## Key Properties

### `RecordClass`

The Active Record class to be edited.

### `RecordPk`

The primary key value of the record to be edited (can be an array for composite keys).

### `EditRenderer`

The class name of a custom edit renderer implementing [`IScaffoldEditRenderer`](./IScaffoldEditRenderer.md).

### `ValidationGroup`

The validation group name for validators (auto-generated based on control ID).

## Child Controls

| Control | Type | Description |
|---------|------|-------------|
| `SaveButton` | `TButton` | Button to save the Active Record |
| `ClearButton` | `TButton` | Button to clear the editor inputs |
| `CancelButton` | `TButton` | Button to cancel the edit action |

## Key Methods

### `initializeEditForm()`

Initializes the editor form, creating input controls based on table columns or using a custom renderer.

### `doSave()`

Validates the page and saves the record.

### `getScaffoldInputBuilder($record)`

Creates the appropriate scaffold input builder based on the database driver.

## Custom Rendering

To provide a custom edit form:

1. Create a class implementing `IScaffoldEditRenderer`
2. Set the `EditRenderer` property to your class name
3. The `updateRecord()` method will be called before save

## Validation

Validators in custom templates should have their `ValidationGroup` property set to the value returned by `getValidationGroup()`.

## See Also

- [TScaffoldBase](./TScaffoldBase.md)
- [IScaffoldEditRenderer](./IScaffoldEditRenderer.md)
- [TScaffoldInputBase](../Scaffold/InputBuilder/TScaffoldInputBase.md)

## Category

ActiveRecord Scaffold
