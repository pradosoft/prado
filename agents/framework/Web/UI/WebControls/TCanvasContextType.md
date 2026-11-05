# Web/UI/WebControls/TCanvasContextType

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TCanvasContextType`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TCanvasContextType.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `TEnumerable`

## Overview
The drawing context a [TCanvas](./TCanvas.md) creates for `ClientSide.OnDraw`.

| Constant | `getContext()` | Notes |
|---|---|---|
| `Context2D` | `'2d'` | Default; scaled to the pixel ratio under MatchDisplaySize |
| `WebGL` | `'webgl'` | `gl.viewport` is the handler's |
| `WebGL2` | `'webgl2'` | `gl.viewport` is the handler's |
| `BitmapRenderer` | `'bitmaprenderer'` | |

**@since 4.4.0**
