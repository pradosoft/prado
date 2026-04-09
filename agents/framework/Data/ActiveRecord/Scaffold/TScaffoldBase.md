# TScaffoldBase

### Directories

[./](../../INDEX.md) > [Data](../../INDEX.md) > [ActiveRecord](../INDEX.md) > [Scaffold](./INDEX.md) > [TScaffoldBase](./TScaffoldBase.md)

`Prado\Data\ActiveRecord\Scaffold\TScaffoldBase`

Abstract base class for Active Record scaffold views.

Inherits from `Prado\Web\UI\TTemplateControl`.

## Description

`TScaffoldBase` is the base class for all scaffold views (`TScaffoldListView`, `TScaffoldEditView`, `TScaffoldListView`, `TScaffoldView`). It provides common properties and methods for scaffolding Active Record data.

During the `OnPrRender` stage, the default CSS style file (`style.css`) is published and registered. To override the default style, provide your own stylesheet file explicitly.

## Key Properties

### `RecordClass`

The name of the Active Record class to be viewed or scaffolded.

### `DefaultStyle`

The default scaffold stylesheet name (default: `'style'`).

### `EnableDefaultStyle`

Enable the default stylesheet (default: `true`).

## Key Methods

### `getTableInfo()`

Returns the [`TDbTableInfo`](../Common/TDbTableInfo.md) for the current record's table.

### `getRecordObject($pk = null)`

Gets the current Active Record instance. Creates a new instance if the primary key is null, otherwise fetches from the database.

### `getRecordFinder()`

Returns the Active Record finder instance.

### `getRecordPropertyValues($record)`

Returns an array of record property values.

### `getRecordPkValues($record)`

Returns an array of record primary key values.

### `onPreRender($param)`

Publishes the default stylesheet file if enabled.

## See Also

- [TScaffoldView](./TScaffoldView.md)
- [TScaffoldListView](./TScaffoldListView.md)
- [TScaffoldEditView](./TScaffoldEditView.md)
- [TScaffoldSearch](./TScaffoldSearch.md)

## Category

ActiveRecord Scaffold
