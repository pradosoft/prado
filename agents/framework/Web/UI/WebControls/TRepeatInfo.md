# Web/UI/WebControls/TRepeatInfo

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TRepeatInfo`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TRepeatInfo.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TRepeatInfo represents repeat information for controls like TCheckBoxList. It specifies the layout of repeated items via RepeatLayout (Table, Flow, Raw, OrderedList, or UnorderedList), RepeatColumns, and RepeatDirection (Vertical or Horizontal).

The OrderedList and UnorderedList layouts (@since 4.4.0) render an `<ol>`/`<ul>` carrying the user's ID, attributes, and style. `renderListContents()` puts the header, each item, and the footer in an `<li>` that carries the item style (`generateItemStyle()`). Separators, the caption, `RepeatColumns`, and `RepeatDirection` do not apply.

The Table and Flow layouts place items by `RepeatDirection`:
- Horizontal: rows of `RepeatColumns` items (all items in one row when 0); a separator follows each item but the last.
- Vertical: columns filled top to bottom (one column when `RepeatColumns` is 0 or 1); a separator follows each item but the item with the last index.
- Table cells missing from the last row or column render as empty `<td>`. The header renders in `<thead>` as a `<th scope="col">`, the footer in `<tfoot>`, each spanning all columns.
- Raw renders the header, the items with separators, and the footer, with no markup of its own.

## Key Properties/Methods

- `getCaption()` / `setCaption($value)` - Table caption
- `getCaptionAlign()` / `setCaptionAlign($value)` - Caption alignment
- `getRepeatColumns()` / `setRepeatColumns($value)` - Number of columns
- `getRepeatDirection()` / `setRepeatDirection($value)` - Vertical or Horizontal
- `getRepeatLayout()` / `setRepeatLayout($value)` - Table, Flow, Raw, OrderedList, or UnorderedList
- `renderRepeater($writer, $user)` - Renders the repeated items

## See Also

- [IRepeatInfoUser](./IRepeatInfoUser.md)
- [TCheckBoxList](./TCheckBoxList.md)
