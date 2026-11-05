# Web/UI/TWebColor

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](./INDEX.md) / **`TWebColor`**

## Class Info
**Location:** `framework/Web/UI/TWebColor.php`
**Namespace:** `Prado\Web\UI`
**Extends:** `Prado\TEnumerable`

## Overview
TWebColor defines the full set of CSS/HTML named colors (Basic and Extended Web Colors) as class constants mapping color names to their `#RRGGBB` hex values.

Used by `TPropertyValue::ensureHexColor()` to convert CSS color name strings to their hex equivalents. `TWebColors` (plural) is the deprecated alias that extends this class and will be removed in v4.4.

## Key Constants

All constants are `string` class constants with `#RRGGBB` hex values (uppercase, 6 hex digits).

### Basic Web Colors (18)
`White`, `Silver`, `Gray`, `Black`, `Red`, `Maroon`, `Orange`, `Yellow`, `Olive`, `Lime`, `Green`, `Aqua`, `Cyan`, `Teal`, `Blue`, `Navy`, `Fuchsia`, `Magenta`, `Purple`

Note: `Aqua === Cyan` (`#00FFFF`) and `Fuchsia === Magenta` (`#FF00FF`).

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

## Patterns & Gotchas

- **Replaces `TWebColors`** — `TWebColors` (plural) now extends `TWebColor` as a deprecated stub and will be removed in v4.4. Prefer `TWebColor` in new code.
- **Used by `TPropertyValue::ensureHexColor()`** — accepts either `#RRGGBB` / `#RGB` hex strings or a CSS color name; resolves names via case-insensitive constant lookup against `TWebColor`.
- **PascalCase constant names** — color names use PascalCase (e.g., `CornflowerBlue`). `TPropertyValue::ensureHexColor()` normalises input case before lookup.
- **`TEnumerable` base** — provides enumeration over the constant list.

## See Also

- [TWebColors](./TWebColors.md) (deprecated alias)

**@since 4.3.0**
