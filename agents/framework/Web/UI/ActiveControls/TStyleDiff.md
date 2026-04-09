# TStyleDiff

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [ActiveControls](./INDEX.md) > [TStyleDiff](./TStyleDiff.md)

**Location:** `framework/Web/UI/ActiveControls/TStyleDiff.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview

Calculates changes to control style properties including CSS class names and inline styles. Extends [TViewStateDiff](./TViewStateDiff.md) to compare style fields and custom styles, returning an array with `CssClass` and `Style` changes.

## Key Properties/Methods

- `getDifference()` - Returns `['CssClass' => $css, 'Style' => $style]` or null object if unchanged
- `getCombinedStyle($obj)` - Combines style fields from TStyle object
- `getStyleFromString($string)` - Parses CSS string to name-value array
- `getCssClassDiff()` - Calculates CSS class name changes
- `getStyleDiff()` - Calculates inline style changes

## See Also

- [TViewStateDiff](./TViewStateDiff.md), [TCallbackPageStateTracker](./TCallbackPageStateTracker.md)
