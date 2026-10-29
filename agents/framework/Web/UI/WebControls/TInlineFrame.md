# Web/UI/WebControls/TInlineFrame

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TInlineFrame`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TInlineFrame.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TInlineFrame displays an inline frame (iframe) on a Web page with configurable URL, dimensions, margins, scrollbars, and borders.

## Key Properties/Methods

- `getFrameUrl()` / `setFrameUrl()` - Gets or sets URL to load in the iframe
- `getData()` / `setData()` - IDataRenderer implementation (same as FrameUrl)
- `getAlign()` / `setAlign()` - Gets or sets iframe alignment (deprecated in HTML5)
- `getWidth()` / `setWidth()` - Gets or sets iframe width
- `getHeight()` / `setHeight()` - Gets or sets iframe height
- `getMarginWidth()` / `setMarginWidth()` - Gets or sets left/right margins (deprecated in HTML5)
- `getMarginHeight()` / `setMarginHeight()` - Gets or sets top/bottom margins (deprecated in HTML5)
- `getScrollBars()` / `setScrollBars()` - Gets or sets scrollbar visibility (deprecated in HTML5)
- `getShowBorder()` / `setShowBorder()` - Gets or sets whether to show border (deprecated in HTML5)
- `getDescriptionUrl()` / `setDescriptionUrl()` - Gets or sets long description URL (deprecated in HTML5)
- `getSrcDoc()` / `setSrcDoc()` - `srcdoc`, an HTML document shown in place of FrameUrl; `&` is encoded too, so the document's own character references survive (@since 4.4.0)
- `getSandbox()` / `setSandbox()` - `sandbox`: null (none), '' or true (every restriction), or tokens such as `allow-scripts allow-forms` (@since 4.4.0)
- `getAllow()` / `setAllow()` - `allow`, the permissions policy, such as `fullscreen; camera 'none'` (@since 4.4.0)
- `getLoading()` / `setLoading()` - `loading`, a `TLoadingMode` (@since 4.4.0)
- `getReferrerPolicy()` / `setReferrerPolicy()` - `referrerpolicy`, a `TReferrerPolicy` (@since 4.4.0)

## Accessibility

`ToolTip` renders the `title` that names the frame (WCAG 4.1.2); every frame takes one that describes its content.

## Gotchas

- `allow-scripts` with `allow-same-origin` on a same-origin frame lets the frame remove its own sandbox.

## See Also

- [TWebControl](./TWebControl.md)
- [TInlineFrameAlign](./TInlineFrameAlign.md)
- [TInlineFrameScrollBars](./TInlineFrameScrollBars.md)
