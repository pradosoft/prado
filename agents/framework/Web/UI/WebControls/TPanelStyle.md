# TPanelStyle

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TPanelStyle](./TPanelStyle.md)

**Location:** `framework/Web/UI/WebControls/TPanelStyle.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TPanelStyle represents the CSS style specific for panel HTML tags. It extends TStyle with panel-specific properties like background image, direction, horizontal alignment, scroll bars, and wrapping.

## Key Properties/Methods

- `getBackImageUrl()` / `setBackImageUrl($value)` - Background image URL
- `getDirection()` / `setDirection($value)` - Content direction (TContentDirection)
- `getWrap()` / `setWrap($value)` - Whether content wraps
- `getHorizontalAlign()` / `setHorizontalAlign($value)` - Horizontal alignment (THorizontalAlign)
- `getScrollBars()` / `setScrollBars($value)` - Scroll bar visibility (TScrollBars)
- `getBoxShadow()` / `setBoxShadow($value)` - Box shadow CSS property
- `reset()` - Resets style to default values
- `copyFrom($style)` - Copies style properties from another style
- `mergeWith($style)` - Merges style with another

## See Also

- [TPanel](./TPanel.md)
- [TStyle](./TStyle.md)
