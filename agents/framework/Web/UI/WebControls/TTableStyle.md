# TTableStyle

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TTableStyle](./TTableStyle.md)

**Location:** `framework/Web/UI/WebControls/TTableStyle.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TTableStyle represents the CSS style specific for HTML tables. It extends TStyle and adds properties for background image, horizontal alignment, cell padding/spacing, grid lines, and border collapse.

## Key Properties/Methods

- `getBackImageUrl()` / `setBackImageUrl(string)` - Background image URL
- `getHorizontalAlign()` / `setHorizontalAlign(THorizontalAlign)` - Table alignment
- `getCellPadding()` / `setCellPadding(int)` - Cell padding (deprecated)
- `getCellSpacing()` / `setCellSpacing(int)` - Cell spacing (deprecated)
- `getGridLines()` / `setGridLines(TTableGridLines)` - Grid line setting (deprecated)
- `getBorderCollapse()` / `setBorderCollapse(bool)` - Whether to collapse borders
- `addAttributesToRender($writer)` - Renders style attributes

## See Also

- [TStyle](./TStyle.md)
- [TTable](./TTable.md)
