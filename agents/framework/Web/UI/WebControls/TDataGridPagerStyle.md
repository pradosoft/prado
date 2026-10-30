# Web/UI/WebControls/TDataGridPagerStyle

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TDataGridPagerStyle`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TDataGridPagerStyle.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TDataGridPagerStyle specifies styles and properties for datagrid pager display.

## Key Properties/Methods

- `Mode` - Pager mode (NextPrev or Numeric)
- `ButtonType` - Button type (LinkButton or PushButton)
- `Position` - Pager position (Top, Bottom, TopAndBottom)
- `PageButtonCount` - Max number of pager buttons (default 10)
- `ListLayout` - Renders the pager buttons as `<ul>` list items (default false; @since 4.4.0)
- `NextPageText` / `PrevPageText` / `FirstPageText` / `LastPageText` - Button text. In link-button and label pager buttons, the default page texts (`<`, `>`, `<<`, `>>`) render HTML-encoded (`TDataGrid::encodeDefaultPagerText()`); any other text renders as HTML. Push and image buttons put the text in an encoded attribute.

## See Also

- [TPanelStyle](./TPanelStyle.md)
