# TTemplateColumn

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TTemplateColumn](./TTemplateColumn.md)

**Location:** `framework/Web/UI/WebControls/TTemplateColumn.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TTemplateColumn customizes the layout of controls in a DataGrid column using templates. It supports ItemTemplate, EditItemTemplate, HeaderTemplate, and FooterTemplate. Since v3.1.0, it also supports ItemRenderer and EditItemRenderer for control-based cell rendering.

## Key Properties/Methods

- `getItemTemplate()` / `setItemTemplate(ITemplate)` - Template for item cells
- `getEditItemTemplate()` / `setEditItemTemplate(ITemplate)` - Template for edit mode
- `getHeaderTemplate()` / `setHeaderTemplate(ITemplate)` - Template for header
- `getFooterTemplate()` / `setFooterTemplate(ITemplate)` - Template for footer
- `getItemRenderer()` / `setItemRenderer(string)` - Class for item cell renderer
- `getEditItemRenderer()` / `setEditItemRenderer(string)` - Class for edit cell renderer
- `initializeCell($cell, $columnIndex, $itemType)` - Initializes cell based on template/renderer

## See Also

- [TDataGridColumn](./TDataGridColumn.md)
- [IDataRenderer](./IDataRenderer.md)
