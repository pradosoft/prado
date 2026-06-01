# Web/UI/TTheme

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](./INDEX.md) / **`TTheme`**

## Class Info
**Location:** `framework/Web/UI/TTheme.php`
**Namespace:** `Prado\Web\UI`

## Overview
TTheme represents a particular theme as a collection of control skins stored in a directory. Each `.skin` file under the theme directory is parsed and saved as control skins. Skins apply initial property values to controls based on their class name and SkinID. TTheme supports caching to save parsing time and handles RTL CSS file prioritization for right-to-left languages.

**Implements:** `ITheme`
**Extends:** `TApplicationComponent`

## Constants

| Constant | Value | Description |
|---|---|---|
| `THEME_CACHE_PREFIX` | `'prado:theme:'` | Prefix for application cache keys storing parsed theme data |
| `SKIN_FILE_EXT` | `'.skin'` | Extension for skin files |

## Key Properties/Methods

- `Name` — Theme directory name (basename of `BasePath`)
- `BaseUrl` — URL to the theme folder
- `BasePath` — Absolute filesystem path to the theme folder
- `Skins` — Array of skins indexed by control class and SkinID
- `StyleSheetFiles` — Sorted list of CSS file URLs in the theme
- `JavaScriptFiles` — Sorted list of JS file URLs in the theme
- `applySkin($control)` — Applies a skin to a control based on its class and SkinID

## Caching

TTheme uses the application cache (`getApplication()->getCache()`) to store `[$skins, $cssFiles, $jsFiles, $timestamp]`. In `Performance` mode the timestamp check is skipped entirely and cached data is returned immediately. Otherwise, the skin file timestamps are compared against the cached timestamp to detect changes.

## RTL Support

All CSS files ending in `.*rtl.css` or `.*rtl.(media).css` are removed from the file list initially. If application globalization is active and the current culture is right-to-left, the RTL CSS files are re-added at the **end** of the list so they take priority over the base styles.

## Dynamic Events (behaviors)

| Event | Signature | Description |
|---|---|---|
| `dyThemeConstruct` | `(string $themePath, string $themeUrl)` | Called in constructor after `parent::__construct()` |
| `dyProcessFile` | `(bool $return, string $file)` | Called per directory entry; return `true` to skip a file |
| `dyThemeProcess` | `()` | Called after all files have been scanned |
| `dyCssMediaType` | `(string $return, string $url)` | Allows behaviors to override the CSS media type for a URL |

## Patterns & Gotchas

- **TSkinTemplate is used for skin files** — skin file parsing uses `TSkinTemplate` (which disables class/attribute validation), not `TTemplate`.
- **Nested controls in skins are forbidden** — a skin entry with a non-`-1` parent index throws `TConfigurationException('theme_control_nested')`.
- **Duplicate SkinIDs throw** — same class + SkinID combination in the same theme throws `TConfigurationException('theme_skinid_duplicated')`.
- **Non-existent theme path** — constructor throws `TIOException('theme_path_inexistent')` if `opendir()` fails.

## See Also

- [TThemeManager](./TThemeManager.md)
- [TSkinTemplate](./TSkinTemplate.md)
- [ITemplate](./ITemplate.md)

(End of file - total 24 lines)
