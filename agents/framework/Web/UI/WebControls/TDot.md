# Web/UI/WebControls/TDot

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TDot`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TDot.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [`TImage`](TImage.md) · **Implements:** `Prado\Web\IPublishable`
**Since:** 4.4.0

## Overview
TDot renders an SVG dot of a given `Size` and `Color`, shaded 3D by default or flat. It is a status indicator. The SVG is generated on the server; it is embedded as a data URI, published as a virtual asset through [`TAssetManager`](../../TAssetManager.md), or written inline into the page.

## Core Properties
| Property | Default | Notes |
|---|---|---|
| `Color` | `null` | A named web color (preset main/highlight pair in `COLORS`), a `#RRGGBB` value, or a `TWebColor`. Setting it computes `MainColor` and `HighlightColor`. |
| `MainColor`, `HighlightColor` | computed | The top/middle color and the bottom "refraction" color. |
| `Depth` | `DEFAULT_DEPTH` | The offset, [0, 255], used to darken `MainColor` and lighten `HighlightColor` from an RGB `Color`. |
| `Size` | `21` | Width and height in pixels. |
| `ShadowOpacity` | `0.618` | Opacity of the 3D drop shadow, clamped to [0, 1]. |
| `Flat`, `FlatBorder`, `FlatBorderWidth` | `false`, `true`, `5%` | A flat circle with an optional border. |
| `PublishStyle` | `Auto` | `Auto`, `Embed`, `Publish`, or `Inline`. `Auto` resolves to `Embed` in `TApplicationMode::Debug`, otherwise `Publish`. |
| `AlternateText` | `''` | Inherited from TImage; the dot's accessible name. Empty means decorative. |

## Publish Styles
| Style | Output | IDs inside the SVG |
|---|---|---|
| `Embed` | `<img src="data:image/svg+xml;base64,…" alt="…">` | plain (`grad`, `hblur`, …) |
| `Publish` | `<img src="<asset url>" alt="…">`; the file name comes from `getDataID()` | plain |
| `Inline` | `<svg>` carrying the control's attributes, with the inner markup as children | prefixed `<ClientID>_` |

`Embed` and `Publish` SVGs are separate documents, so their IDs cannot collide. Inline SVG shares the page's ID namespace: `generateInnerSVG($idPrefix)` prefixes the 3D dot's `hblur`, `highgrad`, `grad`, `shadowBlur`, `hclip`, and `hcclip` IDs and their `url(#…)` references with `getInlineIdPrefix()` (`<ClientID>_`). Without the prefix, a second inline dot's `url(#grad)` resolves to the first dot's gradient and draws in its color. The flat dot has no IDs.

## Accessibility
The `<img>` styles render TImage's `alt`. The `Inline` style has no `alt`, so `addAttributesToRender()` and `renderContents()` render:

| Condition | Output |
|---|---|
| `AlternateText` set | `role="img"`, `aria-labelledby="<ClientID>_title"`, and `<title id="<ClientID>_title">` as the first child of the `<svg>` |
| `AlternateText` empty | `aria-hidden="true"`; decorative, as TImage's `alt=""` is |
| `Role` set | replaces `role="img"`; the name and `aria-hidden` rules are unchanged |

- Chromium exposes a named inline dot as role `image` with the `AlternateText` as its name; a decorative one is ignored.
- The SVG `<title>` also shows as a hover tooltip in browsers.
- `Aria` entries and custom attributes render after these and override them.
- A dot conveys status by color. Pair it with text or an `AlternateText` that states the status (WCAG 1.4.1).

## Tests
- Unit: `tests/unit/Web/UI/WebControls/TDotTest.php`.
- Functional: `tests/playwright/web/TDotTestCase.spec.js` on the harness page `tests/harness/web/protected/pages/DotTest.page` checks the inline accessibility attributes, distinct inline IDs, and the Chromium accessibility tree over CDP.
- Visual: `tests/harness/features/protected/pages/TDotVisual.page` shows every preset color and several sizes.

## Usage Example
```xml
<com:TDot Size="16" Color="LimeGreen" AlternateText="Online" />
<com:TDot Size="16" Color="Red" PublishStyle="Inline" AlternateText="Offline" />
<com:TDot Size="16" Color="Gray" Flat="true" />
```
