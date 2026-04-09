# TRepeatInfo

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TRepeatInfo](./TRepeatInfo.md)

**Location:** `framework/Web/UI/WebControls/TRepeatInfo.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TRepeatInfo represents repeat information for controls like TCheckBoxList. It specifies the layout of repeated items via RepeatLayout (Table, Flow, or Raw), RepeatColumns, and RepeatDirection (Vertical or Horizontal).

## Key Properties/Methods

- `getCaption()` / `setCaption($value)` - Table caption
- `getCaptionAlign()` / `setCaptionAlign($value)` - Caption alignment
- `getRepeatColumns()` / `setRepeatColumns($value)` - Number of columns
- `getRepeatDirection()` / `setRepeatDirection($value)` - Vertical or Horizontal
- `getRepeatLayout()` / `setRepeatLayout($value)` - Table, Flow, or Raw
- `renderRepeater($writer, $user)` - Renders the repeated items

## See Also

- [IRepeatInfoUser](./IRepeatInfoUser.md)
- [TCheckBoxList](./TCheckBoxList.md)
