# Data/ActiveRecord/Scaffold/TScaffoldSearch

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [ActiveRecord](../INDEX.md) / [Scaffold](./INDEX.md) / **`TScaffoldSearch`**

## Class Info
**Location:** `framework/Data/ActiveRecord/Scaffold/TScaffoldSearch.php`
**Namespace:** `Prado\Data\ActiveRecord\Scaffold`

## Overview
`Prado\Data\ActiveRecord\Scaffold\TScaffoldSearch`

Search control for filtering TScaffoldListView records.

Inherits from [`TScaffoldBase`](./TScaffoldBase.md).

## Description

`TScaffoldSearch` provides a simple text box and button to perform search on a `TScaffoldListView`. It filters records based on searchable fields.

## Usage

```php
<com:TScaffoldSearch ID="search" ListViewID="listView" />
```

## Key Properties

### `ListViewID`

The ID of the `TScaffoldListView` this search control belongs to.

### `SearchableFields`

Comma-delimited list of fields that may be searched. Default is null (searches most text-type fields).

## Child Controls

| Control | Type | Description |
|---------|------|-------------|
| `SearchText` | `TTextBox` | Search input text box |
| `SearchButton` | `TButton` | Button with label "Search" |

## Key Methods

### `createSearchCondition()`

Creates the SQL search criteria based on the search text and searchable fields.

### `getFields()`

Returns the list of fields to be searched.

## How Search Works

1. User enters search terms in the text box
2. Clicking Search button triggers `bubbleEvent`
3. The search condition is passed to the associated `TScaffoldListView`
4. The list view filters records using [`TActiveRecordCriteria`](../TActiveRecordCriteria.md)

## Database-Specific Search

The search uses [`TDbCommandBuilder`](../Common/TDbCommandBuilder.md)::getSearchExpression() which is implemented differently for each database driver.

## See Also

- [TScaffoldBase](./TScaffoldBase.md)
- [TScaffoldListView](./TScaffoldListView.md)
- `Prado\Web\UI\WebControls\TTextBox`
- `Prado\Web\UI\WebControls\TButton`

## Category

ActiveRecord Scaffold
