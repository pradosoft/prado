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

## Accessibility

| Drawing | Accessible form |
|---|---|
| Static (chart image, logo) | `AlternateText` → `role="img"` with its `aria-label` |
| Carries data or controls | Fallback content: a table of the values, a button per region; focusable fallback elements are in the tab order. No `AlternateText`, since the children of an `img` are hidden from assistive technology |

## Tests

- `tests/unit/Web/UI/WebControls/TCanvasTest.php`

## See Also

- [THtmlElement](./THtmlElement.md)
- [TImage](./TImage.md) (`AlternateText`)

**@since 4.4.0**
