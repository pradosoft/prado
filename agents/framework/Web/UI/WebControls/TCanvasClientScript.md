# Web/UI/WebControls/TCanvasClientScript

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TCanvasClientScript`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TCanvasClientScript.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `TClientSideOptions`

## Overview
TCanvasClientScript holds the client-side event of a [TCanvas](./TCanvas.md), reached through its `ClientSide` property.

| Property | Description |
|---|---|
| `OnDraw` | JavaScript wrapped as `function(sender, parameter){ ... }`, stored as option `onDraw`; `getOnDraw()` returns the `TJavaScriptLiteral` or null |

`parameter` holds `canvas`, `context`, `width`, `height` (CSS pixels with MatchDisplaySize, else the bitmap size), `pixelRatio`, and `reducedMotion`.

```xml
<com:TCanvas AlternateText="A blue square" ClientSide.OnDraw="
	parameter.context.fillStyle = 'blue';
	parameter.context.fillRect(0, 0, parameter.width / 2, parameter.height / 2);" />
```

**@since 4.4.0**
