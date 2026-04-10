# Data/ActiveRecord/Scaffold/IScaffoldEditRenderer

### Directories
[framework](./INDEX.md) / [Data](./Data/INDEX.md) / [ActiveRecord](./Data/ActiveRecord/INDEX.md) / [Scaffold](./Data/ActiveRecord/Scaffold/INDEX.md) / **`IScaffoldEditRenderer`**

`Prado\Data\ActiveRecord\Scaffold\IScaffoldEditRenderer`

Interface for custom scaffold edit renderers.

## Description

`IScaffoldEditRenderer` defines the interface that an edit renderer needs to implement. An edit renderer provides custom UI for editing Active Record fields in the scaffold system.

## Required Methods

### `updateRecord($record)`

This method should update the record with the user input data.

**Parameters:**
- `$record` (`TActiveRecord`) - The record to be saved

## Additional Requirements

Besides `updateRecord`, an edit renderer should also implement the `IDataRenderer` interface, which provides:

- `getData()` - Returns the current Active Record being edited
- `setData($record)` - Sets the Active Record to be edited

## Usage Example

```php
class MyCustomEditRenderer extends TTemplateControl implements IScaffoldEditRenderer
{
    private $_data;

    public function getData()
    {
        return $this->_data;
    }

    public function setData($record)
    {
        $this->_data = $record;
    }

    public function updateRecord($record)
    {
        $record->title = $this->findControl('titleInput')->getText();
        $record->content = $this->findControl('contentInput')->getText();
    }
}
```

## See Also

- [TScaffoldEditView](./TScaffoldEditView.md)
- `Prado\IDataRenderer`

## Category

ActiveRecord Scaffold
