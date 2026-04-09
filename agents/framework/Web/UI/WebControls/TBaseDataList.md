# TBaseDataList

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TBaseDataList](./TBaseDataList.md)

**Location:** `framework/Web/UI/WebControls/TBaseDataList.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TBaseDataList is the base class for data listing controls (TDataList and TDataGrid). It provides properties for tabular layout presentation including caption, cell padding/spacing, grid lines, and horizontal alignment. It also manages data key fields and data keys storage.

## Key Properties/Methods

- `DataKeyField` - Field from data source providing keys for list items
- `DataKeys` - Collection of key values for each record
- `CellPadding` / `CellSpacing` - Table cell formatting
- `GridLines` - Table border display style
- `HorizontalAlign` - Table content alignment
- `Caption` / `CaptionAlign` - Table caption and alignment
- `onSelectedIndexChanged()` - Event raised when selection changes

## See Also

- [TDataList](./TDataList.md)
- [TDataGrid](./TDataGrid.md)
