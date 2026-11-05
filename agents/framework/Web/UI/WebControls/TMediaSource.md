# Web/UI/WebControls/TMediaSource

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TMediaSource`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TMediaSource.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `TWebControl`

## Overview
TMediaSource represents the HTML `<source>` element: one alternative resource of a [TVideo](./TVideo.md), [TAudio](./TAudio.md), or [TPicture](./TPicture.md). The browser uses the first source whose `Type` and `Media` it supports. `<source>` is void; child controls do not render.

## Template Usage

```xml
<com:TVideo>
	<com:TMediaSource Src="intro.webm" Type="video/webm" />
	<com:TMediaSource Src="intro.mp4" Type="video/mp4" />
</com:TVideo>

<com:TPicture ImageUrl="chart.png" AlternateText="Sales rose 20%">
	<com:TMediaSource Srcset="chart.webp 1x, chart@2x.webp 2x" Type="image/webp" />
</com:TPicture>
```

## Key Properties

| Property | Description |
|---|---|
| `Src` | Media URL (TVideo, TAudio) |
| `Type` | MIME type, with optional `codecs` |
| `Media` | Media query |
| `Srcset` | Image candidates (TPicture) |
| `Sizes` | Display sizes of the Srcset candidates |
| `Width` | Intrinsic width (TPicture); -1 unset; overrides TWebControl's CSS Width |
| `Height` | Intrinsic height (TPicture); -1 unset; overrides TWebControl's CSS Height |

## Accessibility

- A source has no role or content; its media element or image carries the controls, captions, and text alternative.

## Tests

- `tests/unit/Web/UI/WebControls/TMediaSourceTest.php`
- `tests/playwright/web/TMediaTagsTestCase.spec.js` with `tests/harness/web/protected/pages/MediaTagsTest.page`: attributes, captions track loading, source selection, video focus, and roles (Chromium accessibility tree)

## See Also

- [TWebControl](./TWebControl.md)
- [TVideo](./TVideo.md)
- [TAudio](./TAudio.md)
- [TPicture](./TPicture.md)

**@since 4.4.0**
