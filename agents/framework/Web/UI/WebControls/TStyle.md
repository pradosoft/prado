# Web/UI/WebControls/TStyle

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TStyle`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TStyle.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TStyle encapsulates CSS style properties applied to a control. It manages five distinct stores: named CSS fields (`_fields`), a lazy-created `TFont` object (`_font`), a CSS class name (`_class`), a raw custom style string (`_customStyle`), and a logical display style (`_displayStyle`).

## Render Order

`addAttributesToRender` emits in this order (lowest to highest priority):
1. Custom style string (parsed field by field)
2. Named fields from `getRenderStyleFields()`: `_fields`, plus the CSS a subclass derives from its properties (@since 4.4.0)
3. Font CSS from `TFont::addAttributesToRender`
4. CSS class as `class=` attribute

## copyFrom vs mergeWith

- **`copyFrom`** — source wins. Each source field overwrites the target field; source `_class` and `_customStyle` replace target values when non-null.
- **`mergeWith`** — target wins. Source fields fill in only what the target lacks; target keeps its own values when already set.
- Display style: `copyFrom` takes the source's when it differs from the source's `DEFAULT_DISPLAY_STYLE`; `mergeWith` takes the base's when the target's is its default and the base's is not. Both apply it through `setDisplayStyle()` before the fields (@since 4.4.0).

## DisplayStyle

| State | CSS fields written |
|---|---|
| `Fixed` | `visibility:visible`; removes `display:none` |
| `None` | `display:none` |
| `Dynamic` | removes `display` and `visibility` |
| `Hidden` | `visibility:hidden`; removes `display:none` |

`Fixed` and `Hidden` keep a `display` value other than `none`, so any state can follow any other (@since 4.4.0).

## Key Properties/Methods

- `getBackColor()` / `setBackColor($value)` - Background color
- `getBorderColor()` / `setBorderColor($value)` - Border color
- `getBorderStyle()` / `setBorderStyle($value)` - Border style
- `getBorderWidth()` / `setBorderWidth($value)` - Border width
- `getBorderRadius()` / `setBorderRadius($value)` - Border radius
- `getCssClass()` / `setCssClass($value)` - CSS class name
- `getHasCssClass(): bool` - Whether CSS class has been explicitly set (@since 4.3.3)
- `getFont()` - Returns TFont object (lazy-created)
- `getHasFont(): bool` - Whether a TFont instance has been set (@since 4.3.3)
- `getForeColor()` / `setForeColor($value)` - Foreground color
- `getHeight()` / `setHeight($value)` - Height
- `getWidth()` / `setWidth($value)` - Width
- `getCustomStyle()` / `setCustomStyle($value)` - Custom CSS style string
- `getHasCustomStyle(): bool` - Whether a custom style string has been set (@since 4.3.3)
- `getDisplayStyle()` / `setDisplayStyle($value)` - Display style (`TDisplayStyle::Fixed` is the default, @since 4.3.3)
- `getStyleField($name)` / `setStyleField($name, $value)` / `clearStyleField($name)` - Individual CSS property fields. Array syntax supported: `$style['margin'] = '10px'`.
- `getHasStyleFields(): bool` - Whether any named CSS property has been set (@since 4.3.3)
- `reset()` - Resets all state to constructor defaults (including `_displayStyle → DEFAULT_DISPLAY_STYLE`)
- `copyFrom($style)` - Copies from another style (source wins)
- `mergeWith($style)` - Merges with another style (target wins)
- `addAttributesToRender($writer)` - Adds CSS attributes to renderer
- `getRenderStyleFields(): array` (protected) - The fields `addAttributesToRender` writes. A subclass adds its derived CSS with `array_merge()`, so rendering leaves `_fields` unchanged (@since 4.4.0)

## New in 4.3.3

- `DEFAULT_DISPLAY_STYLE` constant (overridable in subclasses via late-static binding)
- `__wakeup()` restores `_displayStyle` to `static::DEFAULT_DISPLAY_STYLE` when missing from serialized data
- `getHasCssClass()`, `getHasFont()`, `getHasCustomStyle()`, `getHasStyleFields()` — introspection helpers
- `setFont(TFont $font)` and `newFont(): TFont` protected methods for subclass customization

## See Also

- [TWebControl](./TWebControl.md)
- [IStyleable](./IStyleable.md)
- [TFont](./TFont.md)
