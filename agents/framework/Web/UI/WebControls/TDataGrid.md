# Web/UI/WebControls/TDataGrid

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TDataGrid`**

## Class Info
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
| [`TRowSelectColumn`](./TRowSelectColumn.md) | Row checkboxes with a select-all header checkbox; `getSelectedIndices()` / `getSelectedKeys()` (@since 4.4.0) |
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
| `SortExpression` | string | Current sort: the sorted column's `SortExpression`, `''` when unsorted. The application sets it (@since 4.4.0) |
| `SortDirection` | `TDataGridSortDirection` | Current sort direction, default `Ascending`. The application sets it (@since 4.4.0) |
| `AllowPaging` | bool | Enable pagination |
| `PageSize` | int | Items per page (default: 10) |
| `CurrentPageIndex` | int | Zero-based current page |
| `VirtualItemCount` | int | Total item count for custom paging |
| `AllowCustomPaging` | bool | Enable server-side paging (sets `VirtualItemCount`) |
| `EditItemIndex` | int | Row index in edit mode (-1 = none) |
| `SelectedItemIndex` | int | Row index selected (-1 = none) |
| `ShowHeader` | bool | Show column headers (default: true) |
| `ShowFooter` | bool | Show column footers (default: false) |
| `Caption` | string | Table caption text, rendered as HTML unless `Encode` is true |
| `Encode` | bool | Whether `Caption` is HTML-encoded when rendered (default: false; 4.4.0) |
| `EmptyTemplate` | ITemplate | Rendered when no data |
| `StickyHeader` | bool | `<thead>` renders `position:sticky; top:0; z-index:1`, with `background-color:Canvas` unless `TableHeadStyle` sets one (default false; 4.4.0) |
| `ResponsiveMode` | [`TDataGridResponsiveMode`](./TDataGridResponsiveMode.md) | `Scroll` wraps the table in `<div role="region" tabindex="0" style="overflow-x:auto">` (default `None`; 4.4.0) |

## Style Properties

`ItemStyle`, `AlternatingItemStyle`, `SelectedItemStyle`, `EditItemStyle`, `HeaderStyle`, `FooterStyle`, `PagerStyle`, `TableHeadStyle`, `TableBodyStyle`, `TableFootStyle`

`applyItemStyles()` merges each item's style:
- `AlternatingItemStyle` inherits `ItemStyle`; `EditItemStyle` inherits `SelectedItemStyle`.
- A selected or edit item also merges `ItemStyle` for an even item index, or `AlternatingItemStyle` for an odd one.

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

## Sorting

A sort link raises `OnSortCommand` with a `TDataGridSortCommandEventParameter`: `SortExpression` is the column's, and `SortDirection` is the direction the click requests from the grid's current sort (`getNextSortDirection()`): `Ascending` for a new expression, the reverse for the current one. The grid does not change its own sort state; the handler applies the sort it performs:

```php
public function sortGrid($sender, $param)
{
    $sender->SortExpression = $param->SortExpression;
    $sender->SortDirection = $param->SortDirection;
    $sender->DataSource = $this->query($sender->SortExpression, $sender->SortDirection);
    $sender->dataBind();
}
```

At render, `applySortState()` sets `aria-sort="ascending|descending"` on the header `<th>` (in `<thead>`, so a column header) of the sortable column whose `SortExpression` equals the grid's, through the cell's ARIA collection, and removes it from the other header cells. No other header cell gets `aria-sort="none"`. Style the indicator with `th[aria-sort="ascending"]` / `th[aria-sort="descending"]`. A grid whose application never sets `SortExpression` renders no `aria-sort`, so an application that sorts one direction only announces no direction it does not perform.

Before raising `OnSortCommand`, `focusSortControl()` sets `TPage::setFocus()` to the sort button's client ID (kept in `getSortFocusID()`), so after the postback a keyboard or screen reader user is back at the header they activated; the header's client IDs are the same after a rebind. A handler's own `setFocus()` wins. `TActiveDataGrid` queues `TCallbackClientScript::focus()` after its `replaceContent()`, since a callback renders no page focus script. (`PRADO_LASTFOCUS` restore is not wired: no control sets the client `TrackFocus` option.)

Accessibility audit (4.4.0, Chromium/Firefox/WebKit): the header `<th>` in `<thead>` is `columnheader`, the role `aria-sort` belongs to; the sort link keeps the header text as its name. Not changed: a sort header is a link (`TLinkButton`, `href="javascript:;"`) for an action, and an image header (`HeaderImageUrl`) without `HeaderText` has no alternate text, so no name.

## Accessibility (4.4.0, #1407)

- **Caption** names the table. With `ResponsiveMode` Scroll the `<caption>` gets the ID `{ClientID}_caption` and names the scroll region through `aria-labelledby`; without a caption the region takes the grid's `aria-label`, else "Table". The region is focusable so arrow keys scroll it (WCAG 2.1.1). The pagers stay outside the region.
- **StickyHeader** sticks to the nearest scrolling ancestor. Inside a Scroll region that is the region, which scrolls vertically only when CSS limits its height.
- **Pagers** render a named `<nav>` landmark with named buttons and `aria-current="page"`; see [TDataGridPager](./TDataGridPager.md) and [TPagerAccessibilityTrait](./TPagerAccessibilityTrait.md). TDataGrid uses the trait for the button names in `buildNextPrevPager()` / `buildNumericPager()`.
- **Row selection**: [TRowSelectColumn](./TRowSelectColumn.md).

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
- **`AutoGenerateColumns=true`** — for prototyping only; no control over rendering or editing. With `AllowSorting`, each auto column sorts by its field name.
- **`DataKeyField`** — important for identifying the correct record on update/delete. Access via `$grid->getDataKeys()[$item->ItemIndex]`.
- **Column order** — columns render in the order they appear in the `<Columns>` block (or in the data for auto-generated).
- **Pager position** — `ShowTopPager`/`ShowBottomPager` control where the pager row appears. Both can be shown simultaneously.
- **`groupCells()`** — internal method that merges adjacent same-value cells when `AllowGrouping` is enabled on a column.
