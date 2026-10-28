# Web/UI/WebControls/TSvg

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TSvg`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TSvg.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `TWebControl` (tag `svg`)

## Overview
TSvg renders an inline `<svg>`. Its content comes from the child controls (template markup), `SvgFile` (a `.svg` file read on the server), or `Svg` (markup as a string). For SvgFile and Svg, the file's root attributes render on the element and the control's properties replace them; child controls render after that content.

## Template Usage

```xml
<com:TSvg ViewBox="0 0 24 24" Width="24" Height="24" AlternateText="Settings">
	<path d="M12 8a4 4 0 1 0 0 8a4 4 0 0 0 0-8z" />
</com:TSvg>
<com:TSvg SvgFile="Application.icons.logo" AlternateText="Prado" />
<com:TSvg SvgFile="Application.icons.chevron" Decorative="true" />
```

## Key Properties

| Property | Description |
|---|---|
| `SvgFile` | File path or namespace (`.svg` extension) |
| `Svg` | SVG markup with a root `<svg>` |
| `ViewBox`, `PreserveAspectRatio` | Rendered as written |
| `Width`, `Height` | CSS pixels as attributes; -1 unset; override TWebControl's CSS Width/Height |
| `AlternateText`, `Description` | Text alternative in a `<title>`, longer description in a `<desc>` |
| `Decorative` | `aria-hidden="true"` |
| `Sanitize` | SvgFile/Svg only; default true; [TSvgDocument](./TSvgDocument.md)`::sanitize()` |
| `PrefixIds` | SvgFile/Svg only; default true; IDs → `<ClientID>_id` with their references |
| `ScopeStyles` | SvgFile/Svg only; default true; CSS selectors prefixed with `#<ClientID>` (EnsureId) |
| `HoistStyles` | SvgFile/Svg only; default true; `<style>` → `registerStyleSheet('TSvg:<md5>')` in `onPreRender()`, deduplicated, with the CSP nonce ([TStyleSheetHelper](../../Javascripts/TStyleSheetHelper.md)) |
| `ReducedMotion` | Registers `@media (prefers-reduced-motion: reduce) { #id, #id * { animation: none !important; transition: none !important; } }`; SMIL is not stopped |

Content is processed once per request, in `onPreRender()`, so hoisted styles reach the head; a render without pre-render processes it then, and `<style>` stays inline when there is no page.

## Accessibility

| Setting | Rendering |
|---|---|
| `AlternateText` | `role="img"`, first child `<title id="<ClientID>_title">`, `aria-labelledby`; Description adds `<desc id="<ClientID>_desc">` and `aria-describedby`; the file's root `<title>`/`<desc>` are removed |
| No AlternateText, file has a root `<title>` | `role="img"`, `aria-labelledby` to that title (given `<ClientID>_title` if it has no ID) |
| `Decorative` | `aria-hidden="true"`; no title |
| None | Rendered as written; Debug mode logs `Prado::warning` naming the control |

- `Role` replaces `role="img"`. An SVG with links or controls uses `Role="group"` and `Aria.label`, since an `img`'s content is hidden from assistive technology.
- `currentColor` keeps shapes visible in forced colors mode.
- Motion over five seconds needs a pause control (WCAG 2.2.2).

## Tests

- `tests/unit/Web/UI/WebControls/TSvgTest.php`
- `tests/playwright/web/TSvgTestCase.spec.js` with `tests/harness/web/protected/pages/SvgTest.page`: names, decorative hiding, group links, sanitizing, per-copy IDs and scoped hoisted styles, reduced motion, and the Chromium accessibility tree

## See Also

- [TSvgDocument](./TSvgDocument.md)
- [TCanvas](./TCanvas.md)
- [TImage](./TImage.md)

**@since 4.4.0**
