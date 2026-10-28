# Web/UI/WebControls/TCanvas

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TCanvas`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TCanvas.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
TCanvas represents the HTML `<canvas>` element, a bitmap that client script draws on. `Width` and `Height` are the bitmap size in pixels; the display size is CSS. The child controls are the fallback content, which the browser shows when it does not render the canvas and which assistive technology reads in its place.

The default tag is `canvas`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
<com:TCanvas Width="400" Height="200" AlternateText="Sales rose 20% in 2026" />

<com:TCanvas Width="400" Height="200">
	<com:TTable Caption="Sales by region">...</com:TTable>
</com:TCanvas>
```

## Key Properties

| Property | Description |
|---|---|
| `Width`, `Height` | Bitmap size in pixels, rendered as `width` and `height`; default -1 (browser default 300 × 150); a negative value restores it. They override `TWebControl`'s CSS `Width`/`Height`, as [TInlineFrame](./TInlineFrame.md) does; the display size is set through `Style` or `CssClass` |
| `AlternateText` | Text alternative of a static drawing: renders `role="img"` (unless `Role` is set) and `aria-label` (unless `Aria.label` is set) |
| `ClientSide.OnDraw` | JavaScript that draws the canvas ([TCanvasClientScript](./TCanvasClientScript.md)); registers the `canvas` script package |
| `MatchDisplaySize` | Bitmap = display size × `devicePixelRatio`, with a `2d` context scaled to CSS pixels; default true |
| `ContextType` | [TCanvasContextType](./TCanvasContextType.md): `Context2D` (default), `WebGL`, `WebGL2`, `BitmapRenderer` |

## Client-Side Drawing

With `ClientSide.OnDraw` set, the client is JavaScript-capable, and the control is enabled, `onPreRender()` sets `EnsureId`, registers the `canvas` package, and registers `new Prado.WebUI.TCanvas(options)` as an end script keyed `prado:<ClientID>`. Without OnDraw, no script is registered.

`Prado.WebUI.TCanvas` (`controls/canvas.js`) calls OnDraw with `(sender, parameter)`; `parameter` holds `canvas`, `context`, `width`, `height`, `pixelRatio`, and `reducedMotion`.

| Event | Result |
|---|---|
| Page load | OnDraw |
| Callback renders the canvas again | The new instance replaces the old one in `Prado.Registry` (the old `ResizeObserver` disconnects) and calls OnDraw on the blank element |
| Display size or pixel ratio changes, MatchDisplaySize true | `ResizeObserver` → bitmap resized → OnDraw |
| Same size and ratio as the last draw | No draw |

- MatchDisplaySize measures `getBoundingClientRect()`. A hidden canvas (size 0) uses the bitmap size and ratio 1 until it is displayed.
- A canvas whose display size follows its bitmap (no CSS width or height) would grow with each resize; its inline style is fixed at the measured size instead.
- The context is created once; a canvas keeps the type of its first context. A `2d` context gets `setTransform(ratio, 0, 0, ratio, 0, 0)` before each draw.
- Animation longer than five seconds needs a pause control (WCAG 2.2.2); `reducedMotion` reports `prefers-reduced-motion: reduce`.
- A drawing that changes in a callback updates `AlternateText` or the fallback content in the same callback.

## Accessibility

| Drawing | Accessible form |
|---|---|
| Static (chart image, logo) | `AlternateText` → `role="img"` with its `aria-label` |
| Carries data or controls | Fallback content: a table of the values, a button per region; focusable fallback elements are in the tab order. No `AlternateText`, since the children of an `img` are hidden from assistive technology |

## Tests

- `tests/unit/Web/UI/WebControls/TCanvasTest.php`
- `tests/js/controls/canvas.test.js`
- `tests/playwright/web/TStructureTagsTestCase.spec.js` with `tests/harness/web/protected/pages/StructureTagsTest.page`: roles (Chromium accessibility tree), menu postback, canvas fallback focus, and canvas drawing on load, resize, callback, and at pixel ratio 2

## See Also

- [THtmlElement](./THtmlElement.md)
- [TImage](./TImage.md) (`AlternateText`)
- [TCanvasClientScript](./TCanvasClientScript.md), [TCanvasContextType](./TCanvasContextType.md)

**@since 4.4.0**
