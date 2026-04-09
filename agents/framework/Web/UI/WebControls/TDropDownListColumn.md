# TDropDownListColumn

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TDropDownListColumn](./TDropDownListColumn.md)

**Location:** `framework/Web/UI/WebControls/TDropDownListColumn.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TDropDownListColumn represents a column bound to a field in a data source for use in data grid controls. It displays cells with dropdown lists for editing data.

## Key Properties/Methods

- `getDataTextField()` / `setDataTextField()` - Field for cell text content
- `getDataTextFormatString()` / `setDataTextFormatString()` - Formatting for displayed text
- `getDataValueField()` / `setDataValueField()` - Field for dropdown selection
- `getReadOnly()` / `setReadOnly()` - Whether items can be edited
- `getListDataSource()` / `setListDataSource()` - Data source for dropdown lists
- `getListValueField()` / `setListValueField()` - Data field for dropdown item values
- `getListTextField()` / `setListTextField()` - Data field for dropdown item texts
- `getListTextFormatString()` / `setListTextFormatString()` - Format for list item texts
- `initializeCell()` - Initializes cell with dropdown or static text
- `dataBindColumn()` - Databinds a cell in the column

## See Also

- [TDropDownList](./TDropDownList.md)
- [TDataGridColumn](./TDataGridColumn.md)
- [TListItem](./TListItem.md)
