# TDataGrid

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TDataGrid](./TDataGrid.md)

**Location:** `framework/Web/UI/WebControls/TDataGrid.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

Tabular data display control with built-in paging, sorting, and inline editing. Renders an HTML `<table>`. Columns can be auto-generated or explicitly defined. Implements `INamingContainer`.

Extends `[TBaseDataList](./TBaseDataList.md)`.

## Command Constants

```php
TDataGrid::CMD_SELECT     = 'Select'
TDataGrid::CMD_EDIT       = 'Edit'
TDataGrid::CMD_UPDATE     = 'Update'
TDataGrid::CMD_DELETE     = 'Delete'
TDataGrid::CMD_CANCEL     = 'Cancel'
TDataGrid::CMD_SORT       = 'Sort'
TDataGrid::CMD_PAGE       = 'Page'
TDataGrid::CMD_PAGE_NEXT  = 'Next'
TDataGrid::CMD_PAGE_PREV  = 'Previous'
TDataGrid::CMD_PAGE_FIRST = 'First'
TDataGrid::CMD_PAGE_LAST  = 'Last'
```

## Column Types

| Class | Purpose |
|-------|---------|
| `TBoundColumn` | Binds to a data field; read-only display |
| `TButtonColumn` | Renders a button/link per row (Select, Edit, Delete, Custom) |
| `TEditCommandColumn` | Edit/Update/Cancel button group for inline editing |
| `TCheckBoxColumn` | Checkbox column (read-only or editable) |
| `TDropDownListColumn` | Drop-down in edit mode |
| `TTemplateColumn` | Custom `ItemTemplate`, `EditItemTemplate`, `HeaderTemplate`, `FooterTemplate` |
| `TAutoIdColumn` | Auto-incrementing row number |
| `TAutoGenerateColumns` mode | Generates `TBoundColumn` per data field (prototyping only) |

## Key Properties

| Property | Type | Description |
|----------|------|-------------|
| `DataSource` / `DataSourceID` | mixed | Data to bind |
| `DataKeyField` | string | Field name for data keys (accessible via `getDataKeys()`) |
| `Columns` | TDataGridColumnCollection | Explicit column definitions |
| `AutoGenerateColumns` | bool | Auto-generate columns from data (default: false) |
| `AllowSorting` | bool | Enable sort links in column headers |
| `AllowPaging` | bool | Enable pagination |
| `PageSize` | int | Items per page (default: 10) |
| `CurrentPageIndex` | int | Zero-based current page |
| `VirtualItemCount` | int | Total item count for custom paging |
| `AllowCustomPaging` | bool | Enable server-side paging (sets `VirtualItemCount`) |
| `EditItemIndex` | int | Row index in edit mode (-1 = none) |
| `SelectedItemIndex` | int | Row index selected (-1 = none) |
| `ShowHeader` | bool | Show column headers (default: true) |
| `ShowFooter` | bool | Show column footers (default: false) |
| `Caption` | string | Table caption text |
| `EmptyTemplate` | ITemplate | Rendered when no data |

## Style Properties

`ItemStyle`, `AlternatingItemStyle`, `SelectedItemStyle`, `EditItemStyle`, `HeaderStyle`, `FooterStyle`, `PagerStyle`, `TableHeadStyle`, `TableBodyStyle`, `TableFootStyle`

## Events

| Event | When |
|-------|------|
| `OnItemCreated` | After a row control is created |
| `OnItemDataBound` | After a row is data-bound |
| `OnEditCommand` | User clicks Edit button |
| `OnUpdateCommand` | User clicks Update button |
| `OnDeleteCommand` | User clicks Delete button |
| `OnCancelCommand` | User clicks Cancel button |
| `OnItemCommand` | Any button command (generic) |
| `OnSortCommand` | User clicks a sortable column header |
| `OnPageIndexChanged` | User navigates to a different page |
| `OnPagerCreated` | After pager row is created |

## Key Methods

```php
$grid->dataBind(): void                   // bind data and render
$grid->getItems(): TDataGridItemCollection // all data rows
$grid->getItemCount(): int
$grid->getColumns(): TDataGridColumnCollection
$grid->getAutoColumns(): TDataGridColumnCollection  // auto-generated columns
$grid->getHeader(): ?TDataGridItem
$grid->getFooter(): ?TDataGridItem
$grid->getTopPager(): ?TDataGridItem
$grid->getBottomPager(): ?TDataGridItem
$grid->getSelectedItem(): ?TDataGridItem
$grid->getEditItem(): ?TDataGridItem
```

## Paging

```php
// Standard paging (loads all data, slices per page):
$grid->AllowPaging = true;
$grid->PageSize = 20;
$grid->CurrentPageIndex = 0;

// Custom paging (supply only one page of data):
$grid->AllowCustomPaging = true;
$grid->VirtualItemCount = 500;   // total records in DB
$grid->PageSize = 20;
// In OnPageIndexChanged: re-query DB for the new page
```

## Inline Editing Pattern

```php
// In template:
// <com:TDataGrid OnEditCommand="editRow" OnUpdateCommand="updateRow"
//                OnCancelCommand="cancelRow" OnDeleteCommand="deleteRow">

protected function editRow($sender, $param)
{
    $this->grid->EditItemIndex = $param->Item->ItemIndex;
    $this->loadData();
}

protected function updateRow($sender, $param)
{
    $item = $param->Item;
    // read from $item->Cells[1]->Controls[0] etc.
    $this->grid->EditItemIndex = -1;
    $this->loadData();
}
```

## Patterns & Gotchas

- **Always re-bind on postback events** — sorting, paging, edit, and update handlers must call `dataBind()` after updating state.
- **`AutoGenerateColumns=true`** — for prototyping only; disables sorting, custom rendering, and editing support.
- **`DataKeyField`** — important for identifying the correct record on update/delete. Access via `$grid->getDataKeys()[$item->ItemIndex]`.
- **Column order** — columns render in the order they appear in the `<Columns>` block (or in the data for auto-generated).
- **Pager position** — `ShowTopPager`/`ShowBottomPager` control where the pager row appears. Both can be shown simultaneously.
- **`groupCells()`** — internal method that merges adjacent same-value cells when `AllowGrouping` is enabled on a column.
