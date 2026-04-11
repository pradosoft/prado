# Web/UI/WebControls/TStyle

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TStyle`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TStyle.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TStyle encapsulates CSS style properties applied to a control. It manages fields like background color, border styles, font, CSS class, and custom styles.

## Key Properties/Methods

- `getBackColor()` / `setBackColor($value)` - Background color
- `getBorderColor()` / `setBorderColor($value)` - Border color
- `getBorderStyle()` / `setBorderStyle($value)` - Border style
- `getBorderWidth()` / `setBorderWidth($value)` - Border width
- `getBorderRadius()` / `setBorderRadius($value)` - Border radius
- `getCssClass()` / `setCssClass($value)` - CSS class name
- `getFont()` - Returns TFont object
- `getForeColor()` / `setForeColor($value)` - Foreground color
- `getHeight()` / `setHeight($value)` - Height
- `getWidth()` / `setWidth($value)` - Width
- `getCustomStyle()` / `setCustomStyle($value)` - Custom CSS style string
- `getDisplayStyle()` / `setDisplayStyle($value)` - Display style (None, Dynamic, Fixed, Hidden)
- `getStyleField($name)` / `setStyleField($name, $value)` - Individual style fields
- `reset()` - Resets to default state
- `copyFrom($style)` - Copies from another style
- `mergeWith($style)` - Merges with another style
- `addAttributesToRender($writer)` - Adds CSS attributes to renderer

## See Also

- [TWebControl](./TWebControl.md)
- [IStyleable](./IStyleable.md)
- [TFont](./TFont.md)
