# Data/ActiveRecord/Scaffold/TScaffoldView

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [ActiveRecord](../INDEX.md) / [Scaffold](./INDEX.md) / **`TScaffoldView`**

## Class Info
**Location:** `framework/Data/ActiveRecord/Scaffold/TScaffoldView.php`
**Namespace:** `Prado\Data\ActiveRecord\Scaffold`

## Overview
`Prado\Data\ActiveRecord\Scaffold\TScaffoldView`

Composite scaffold control for viewing, editing, and searching Active Records.

Inherits from [`TScaffoldBase`](./TScaffoldBase.md).

## Description

`TScaffoldView` is a composite control consisting of `TScaffoldListView` with a `TScaffoldSearch`. It displays a `TScaffoldEditView` when an "edit" command is raised from the list view (when the edit button is clicked). The "add" button shows an empty form for creating new records.

## Child Controls

| Control | Type | Description |
|---------|------|-------------|
| `ListView` | `TScaffoldListView` | Displays record data |
| `EditView` | `TScaffoldEditView` | Renders inputs for editing/adding records |
| `SearchControl` | `TScaffoldSearch` | Search user interface |
| `AddButton` | `TButton` | "Add new record" button |

## Usage

```php
<com:TScaffoldView ID="scaffold" RecordClass="UserRecord" />
```

## Key Methods

### `bubbleEvent($sender, $param)`

Handles "edit", "new", and default commands. Shows the appropriate view based on the command.

### `showEditView($sender, $param)`

Shows the edit record view.

### `showListView($sender, $param)`

Shows the view for listing records.

### `showAddView($sender, $param)`

Shows the add record view.

## Events

The `TScaffoldView` responds to the following command names:
- `edit` - Shows the edit view for the selected record
- `new` - Shows the add view with empty data
- Default - Shows the list view

## See Also

- [TScaffoldBase](./TScaffoldBase.md)
- [TScaffoldListView](./TScaffoldListView.md)
- [TScaffoldEditView](./TScaffoldEditView.md)
- [TScaffoldSearch](./TScaffoldSearch.md)

## Category

ActiveRecord Scaffold
