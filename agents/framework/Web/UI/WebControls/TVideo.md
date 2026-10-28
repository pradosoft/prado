# Web/UI/WebControls/TVideo

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TVideo`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TVideo.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `TBaseMedia`

## Overview
TVideo represents the HTML `<video>` element. [TBaseMedia](./TBaseMedia.md) holds the playback properties and the accessibility rules.

The default tag is `video`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
<com:TVideo Width="640" Height="360" Poster="intro.jpg">
	<com:TMediaSource Src="intro.webm" Type="video/webm" />
	<com:TMediaSource Src="intro.mp4" Type="video/mp4" />
	<com:TTextTrack Src="intro.en.vtt" Kind="Captions" SrcLang="en" Label="English" IsDefault="true" />
	<a href="intro.mp4">Download the video</a>
</com:TVideo>
```

## Key Properties

| Property | Description |
|---|---|
| `Width` | Display width in CSS pixels; reserves space before load; -1 unset; overrides TWebControl's CSS Width |
| `Height` | Display height in CSS pixels; -1 unset |
| `Poster` | Image shown before playback; decorative |
| `PlaysInline` | Plays in the page on a phone instead of full screen |

## Accessibility

- Recorded video with sound has a `Captions` [TTextTrack](./TTextTrack.md).
- The poster is decorative; the subject is in the surrounding text or a caption.
- See [TBaseMedia](./TBaseMedia.md).

## Tests

- `tests/unit/Web/UI/WebControls/TVideoTest.php`

## See Also

- [THtmlElement](./THtmlElement.md)
- [TBaseMedia](./TBaseMedia.md)
- [TAudio](./TAudio.md)
- [TTextTrack](./TTextTrack.md)
- [TMediaSource](./TMediaSource.md)

**@since 4.4.0**
