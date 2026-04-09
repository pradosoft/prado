# TLiteralColumn

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TLiteralColumn](./TLiteralColumn.md)

**Location:** `framework/Web/UI/WebControls/TLiteralColumn.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TLiteralColumn represents a static text column bound to a field in a data source. Cells display formatted text from the data field, or static text if no data field is specified.

## Key Properties/Methods

- `getDataField()` / `setDataField()` - Gets or sets field name from data source
- `getDataFormatString()` / `setDataFormatString()` - Gets or sets formatting string for display
- `getText()` / `setText()` - Gets or sets static text when no DataField specified
- `getEncode()` / `setEncode()` - Gets or sets whether text should be HTML-encoded
- `initializeCell()` - Initializes cell with static text or databinding
- `dataBindColumn()` - Databinds a cell in the column

## See Also

- [TDataGridColumn](./TDataGridColumn.md)
