# Web/UI/TWebColors

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](./INDEX.md) / **`TWebColors`**

## Class Info
**Location:** `framework/Web/UI/TWebColors.php`
**Namespace:** `Prado\Web\UI`

## Overview
Enumerable class defining all 147+ CSS named colors mapped to their hexadecimal web color values. Extends `TEnumerable`.

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

None beyond those inherited from `TEnumerable`. Color lookup is done via PHP's constant access: `TWebColors::CornflowerBlue` returns `'#6495ED'`.

## Patterns & Gotchas

- **Used by `TPropertyValue::ensureHexColor()`** — that method accepts either a hex string (`#RRGGBB`, `#RGB`) or a CSS color name; it resolves names against `TWebColors` constants using case-insensitive lookup.
- **Alias pairs** — `Aqua === Cyan` (`#00FFFF`) and `Fuchsia === Magenta` (`#FF00FF`). Both names map to the same hex value per the CSS specification.
- **PascalCase constant names** — all color names use PascalCase (e.g., `CornflowerBlue`, not `cornflowerblue`). `TPropertyValue::ensureHexColor()` must normalize case before lookup.
- **`TEnumerable` base class** — provides enumeration behavior allowing color names to be iterated or validated as an enum type.
- **`@since 4.3.0`** — added in 4.3.0; previously color name handling was done elsewhere.
- **147+ colors** — the full W3C extended web color set as defined at https://en.wikipedia.org/wiki/Web_colors.

(End of file - total 46 lines)
