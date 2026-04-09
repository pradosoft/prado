# TScaffoldListView

### Directories

[./](../../INDEX.md) > [Data](../../INDEX.md) > [ActiveRecord](../INDEX.md) > [Scaffold](./INDEX.md) > [TScaffoldListView](./TScaffoldListView.md)

`Prado\Data\ActiveRecord\Scaffold\TScaffoldListView`

Displays a paginated, sortable list of Active Records.

Inherits from [`TScaffoldBase`](./TScaffoldBase.md).

## Description

`TScaffoldListView` displays a list of Active Records with support for sorting, pagination, searching, and record management (edit/delete).

## Child Controls

| Control | Type | Description |
|---------|------|-------------|
| `Header` | `TRepeater` | Displays Active Record property/field names |
| `Sort` | `TDropDownList` | Sorting options (ASC/DESC for each property) |
| `Pager` | `TPager` | Pagination links/buttons |
| `List` | `TRepeater` | Renders rows of Active Record data |

## Usage

```php
<com:TScaffoldListView ID="listView" 
    RecordClass="UserRecord"
    EditViewID="editView"
    SearchCondition="active = 1"
    SearchParameters="[]" />
```

## Key Properties

### `SearchCondition`

SQL search condition (the WHERE clause) for filtering records.

### `SearchParameters`

Array of search parameters for the search condition.

### `EditViewID`

The ID of the `TScaffoldEditView` control for editing records.

## Key Methods

### `loadRecordData()`

Fetches records and data binds them to the list.

### `getRecordCriteria()`

Builds the [`TActiveRecordCriteria`](../TActiveRecordCriteria.md) with sort/search/paging options.

### `deleteRecord($sender, $param)`

Deletes an Active Record.

## Events

Responds to command events:
- `delete` - Deletes the record for that row
- `edit` - Initializes the edit view with the selected record's data

## See Also

- [TScaffoldBase](./TScaffoldBase.md)
- [TScaffoldEditView](./TScaffoldEditView.md)
- [TScaffoldSearch](./TScaffoldSearch.md)
- `Prado\Web\UI\WebControls\TRepeater`
- `Prado\Web\UI\WebControls\TPager`

## Category

ActiveRecord Scaffold
