# Web/UI/WebControls/TCheckBoxList

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TCheckBoxList`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TCheckBoxList.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TCheckBoxList displays a list of checkboxes on a Web page. Supports multiple selection and various layout options.

## Key Properties/Methods

- `RepeatLayout` - How items are displayed (Table, Flow, Raw)
- `RepeatColumns` - Number of columns for display
- `RepeatDirection` - Vertical or Horizontal traversal
- `CellPadding` / `CellSpacing` - Table cell formatting
- `TextAlign` - Alignment of text next to checkboxes
- `Items` - Collection of list items
- `SelectedIndices` - Indices of selected items
- `SelectedValues` - Values of selected items

## Rendering
| Layout | Container (has the list's id, attributes, style, and `role`) |
|---|---|
| Table | `<table>` from `TRepeatInfo` |
| Flow | `<span>` from `TRepeatInfo` |
| Raw | `<span>` from `render()`; items render directly inside |

- `AccessKey` and `TabIndex` move to each item; the container renders neither.
- `getTagName()` returns `table` or `span`, so `TListControl` renders `multiple` only on a `<select>`.
- With no items, only the Raw layout renders its span (with the id and group attributes).
- `addAttributesToRender()` registers the AutoPostBack client script once.

## See Also

- [TListControl](./TListControl.md)
- [TCheckBoxItem](./TCheckBoxItem.md)
