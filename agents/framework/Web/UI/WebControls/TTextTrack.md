# Web/UI/WebControls/TTextTrack

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TTextTrack`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TTextTrack.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `TWebControl`

## Overview
TTextTrack represents the HTML `<track>` element: a WebVTT text track of a [TVideo](./TVideo.md) or [TAudio](./TAudio.md). `<track>` is void; child controls do not render. The `.vtt` file is same-origin, or cross-origin with CORS and the media element's `CrossOrigin`.

## Template Usage

```xml
<com:TVideo Src="intro.mp4">
	<com:TTextTrack Src="intro.en.vtt" Kind="Captions" SrcLang="en" Label="English" IsDefault="true" />
</com:TVideo>
```

## Key Properties

| Property | Description |
|---|---|
| `Src` | URL of the `.vtt` file |
| `Kind` | [TTextTrackKind](./TTextTrackKind.md); default Subtitles; `kind` always renders, lowercase |
| `SrcLang` | BCP 47 language; required for Subtitles |
| `Label` | Name in the browser's track menu |
| `IsDefault` | Renders `default`: the track shows when no user preference applies |

## Accessibility

- Recorded video with sound has a `Captions` track (WCAG 1.2.2).
- Each track has a `Label`, which names it in the track menu.
- Browsers rarely voice `Descriptions`; audio description is a described version or a transcript (WCAG 1.2.5).

## Tests

- `tests/unit/Web/UI/WebControls/TTextTrackTest.php`

## See Also

- [TWebControl](./TWebControl.md)
- [TVideo](./TVideo.md)
- [TAudio](./TAudio.md)
- [TTextTrackKind](./TTextTrackKind.md)

**@since 4.4.0**
