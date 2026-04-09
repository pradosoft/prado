# TActiveDataGrid

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [ActiveControls](./INDEX.md) > [TActiveDataGrid](./TActiveDataGrid.md)

**Location:** `framework/Web/UI/ActiveControls/TActiveDataGrid.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview

Data-bound grid control using callbacks instead of postbacks. Supports paging, sorting, and editing through active controls. Uses surrounding div container for client-side content replacement. Includes active column counterparts ([TActiveBoundColumn](./TActiveBoundColumn.md), [TActiveLiteralColumn](./TActiveLiteralColumn.md), [TActiveCheckBoxColumn](./TActiveCheckBoxColumn.md), etc.).

## Key Properties/Methods

- `setDataSource($value)` - Sets data source and triggers pager rendering
- `getSurroundingTag()` / `setSurroundingTag($value)` - Container tag (default 'div')
- `getSurroundingTagID()` - Returns container element ID
- `createPagerButton(...)` - Creates [TActiveLinkButton](./TActiveLinkButton.md) or [TActiveButton](./TActiveButton.md) for paging
- `createPager()` - Creates [TActiveDataGridPager](./TActiveDataGridPager.md) instance
- `render($writer)` - Renders datagrid with deferred rendering support

## See Also

- `TDataGrid`, [TActiveDataGridPager](./TActiveDataGridPager.md), `ISurroundable`
