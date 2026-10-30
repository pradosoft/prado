# Web/UI/WebControls/TRepeatInfo

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TRepeatInfo`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TRepeatInfo.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TRepeatInfo represents repeat information for controls like TCheckBoxList. It specifies the layout of repeated items via RepeatLayout (Table, Flow, Raw, OrderedList, or UnorderedList), RepeatColumns, and RepeatDirection (Vertical or Horizontal).

The OrderedList and UnorderedList layouts (@since 4.4.0) render an `<ol>`/`<ul>` carrying the user's ID, attributes, and style. `renderListContents()` puts the header, each item, and the footer in an `<li>` that carries the item style (`generateItemStyle()`). Separators, the caption, `RepeatColumns`, and `RepeatDirection` do not apply.

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
