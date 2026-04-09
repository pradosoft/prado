# TTheme

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [TTheme](./TTheme.md)

**Location:** `framework/Web/UI/TTheme.php`
**Namespace:** `Prado\Web\UI`

## Overview

TTheme represents a particular theme as a collection of control skins stored in a directory. Each `.skin` file under the theme directory is parsed and saved as control skins. Skins apply initial property values to controls based on their class name and SkinID. TTheme supports caching to save parsing time and handles RTL CSS file prioritization for right-to-left languages.

## Key Properties/Methods

- `Name` - Theme directory name
- `BaseUrl` - URL to the theme folder
- `BasePath` - File path to the theme folder
- `Skins` - Array of skins indexed by control type and SkinID
- `StyleSheetFiles` - List of CSS files in the theme
- `JavaScriptFiles` - List of JavaScript files in the theme
- `applySkin($control)` - Applies a skin to a control based on its class and SkinID

## See Also

- [TThemeManager](./TThemeManager.md)
- [TSkinTemplate](./TSkinTemplate.md)
- [ITemplate](./ITemplate.md)

(End of file - total 24 lines)
