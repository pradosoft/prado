# Web/UI/ActiveControls/TActiveDataGrid

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TActiveDataGrid`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TActiveDataGrid.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Data-bound grid control using callbacks instead of postbacks. Supports paging, sorting, and editing through active controls. Uses surrounding div container for client-side content replacement. Includes active column counterparts ([TActiveBoundColumn](./TActiveBoundColumn.md), [TActiveLiteralColumn](./TActiveLiteralColumn.md), [TActiveCheckBoxColumn](./TActiveCheckBoxColumn.md), etc.).

## Key Properties/Methods

- `setDataSource($value)` - Sets data source and triggers pager rendering
- `getSurroundingTag()` / `setSurroundingTag($value)` - Container tag (default 'div')
- `getSurroundingTagID()` - Returns container element ID
- `createPagerButton(...)` - Creates [TActiveLinkButton](./TActiveLinkButton.md) or [TActiveButton](./TActiveButton.md) for paging; encodes a default page text for link buttons and labels, as [TDataGrid](../WebControls/TDataGrid.md) does
- `createPager()` - Creates [TActiveDataGridPager](./TActiveDataGridPager.md) instance
- `render($writer)` - Renders datagrid with deferred rendering support; after `replaceContent()`, a sort command's button receives focus (`TDataGrid::getSortFocusID()`, @since 4.4.0)

## See Also

- `TDataGrid`, [TActiveDataGridPager](./TActiveDataGridPager.md), `ISurroundable`
