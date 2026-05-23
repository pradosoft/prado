# Web/UI/TWebColors

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](./INDEX.md) / **`TWebColors`**

## Class Info
**Location:** `framework/Web/UI/TWebColors.php`
**Namespace:** `Prado\Web\UI`

## Overview
**Deprecated** — `TWebColors` is now a thin stub that extends [TWebColor](./TWebColor.md) (singular). All color constants live in `TWebColor`; `TWebColors` exists only for backward compatibility and will be removed in v4.4.

Use [TWebColor](./TWebColor.md) in all new code.

Used by `TPropertyValue::ensureHexColor()` to convert CSS color name strings (e.g., `'CornflowerBlue'`) to their `#RRGGBB` hex equivalents for use in HTML attributes and CSS properties.

## Key Constants

All constants are `string` class constants with `#RRGGBB` hex values (uppercase, 6 hex digits). Organized into groups:

### Basic Web Colors (16)
`White`, `Silver`, `Gray`, `Black`, `Red`, `Maroon`, `Orange`, `Yellow`, `Olive`, `Lime`, `Green`, `Aqua`, `Cyan`, `Teal`, `Blue`, `Navy`, `Fuchsia`, `Magenta`, `Purple`

Note: `Aqua` and `Cyan` are aliases (`#00FFFF`); `Fuchsia` and `Magenta` are aliases (`#FF00FF`).

### Extended Web Colors (grouped)
- **Gray tones:** `DarkSlateGray`, `DimGray`, `SlateGray`, `LightSlateGray`, `DarkGray`, `LightGray`, `Gainsboro`
- **White tones:** `MistyRose`, `AntiqueWhite`, `Linen`, `Beige`, `WhiteSmoke`, `LavenderBlush`, `OldLace`, `AliceBlue`, `Seashell`, `GhostWhite`, `Honeydew`, `FloralWhite`, `Azure`, `MintCream`, `Snow`, `Ivory`
- **Pink:** `MediumVioletRed`, `DeepPink`, `PaleVioletRed`, `HotPink`, `LightPink`, `Pink`
- **Red:** `DarkRed`, `Firebrick`, `Crimson`, `IndianRed`, `LightCoral`, `Salmon`, `DarkSalmon`, `LightSalmon`
- **Orange:** `OrangeRed`, `Tomato`, `DarkOrange`, `Coral`
- **Yellow:** `DarkKhaki`, `Gold`, `Khaki`, `PeachPuff`, `PaleGoldenrod`, `Moccasin`, `PapayaWhip`, `LightGoldenrodYellow`, `LemonChiffon`, `LightYellow`
- **Brown:** `Brown`, `SaddleBrown`, `Sienna`, `Chocolate`, `DarkGoldenrod`, `Peru`, `RosyBrown`, `Goldenrod`, `SandyBrown`, `Tan`, `Burlywood`, `Wheat`, `NavajoWhite`, `Bisque`, `BlanchedAlmond`, `Cornsilk`
- **Green:** `DarkGreen`, `DarkOliveGreen`, `ForestGreen`, `SeaGreen`, `OliveDrab`, `MediumSeaGreen`, `LimeGreen`, `SpringGreen`, `MediumSpringGreen`, `DarkSeaGreen`, `MediumAquamarine`, `YellowGreen`, `LawnGreen`, `Chartreuse`, `LightGreen`, `GreenYellow`, `PaleGreen`
- **Cyan:** `DarkCyan`, `LightSeaGreen`, `CadetBlue`, `DarkTurquoise`, `MediumTurquoise`, `Turquoise`, `Aquamarine`, `PaleTurquoise`, `LightCyan`
- **Blue:** `MidnightBlue`, `DarkBlue`, `MediumBlue`, `RoyalBlue`, `SteelBlue`, `DodgerBlue`, `DeepSkyBlue`, `CornflowerBlue`, `SkyBlue`, `LightSkyBlue`, `LightSteelBlue`, `LightBlue`, `PowderBlue`
- **Purple/Violet/Magenta:** `Indigo`, `DarkMagenta`, `DarkViolet`, `DarkSlateBlue`, `BlueViolet`, `DarkOrchid`, `SlateBlue`, `MediumSlateBlue`, `MediumOrchid`, `MediumPurple`, `Orchid`, `Violet`, `Plum`, `Thistle`, `Lavender`
- **Other:** `RebeccaPurple`

## Key Methods

All color constants are inherited from [TWebColor](./TWebColor.md). Color lookup is done via PHP's constant access: `TWebColors::CornflowerBlue` returns `'#6495ED'` (inherited).

## Patterns & Gotchas

- **Deprecated** — use `TWebColor` (singular). `TWebColors` will be removed in v4.4.
- **All constants are in `TWebColor`** — `TWebColors` inherits them and adds nothing new.
- **@since 4.3.0** — added in 4.3.0; deprecated as of 4.3.3.

## See Also

- [TWebColor](./TWebColor.md) — canonical class with all color constants

(End of file - total 46 lines)
