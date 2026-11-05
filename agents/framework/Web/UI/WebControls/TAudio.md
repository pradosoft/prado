# Web/UI/WebControls/TAudio

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TAudio`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TAudio.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `TBaseMedia`

## Overview
TAudio represents the HTML `<audio>` element. [TBaseMedia](./TBaseMedia.md) holds the playback properties and the accessibility rules.

The default tag is `audio`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
<com:TAudio Preload="Metadata">
	<com:TMediaSource Src="talk.opus" Type="audio/ogg; codecs=opus" />
	<com:TMediaSource Src="talk.mp3" Type="audio/mpeg" />
	<a href="talk.mp3">Download the talk</a>
</com:TAudio>
```

## Accessibility

- Recorded audio has a transcript on or linked from the page (WCAG 1.2.1).
- See [TBaseMedia](./TBaseMedia.md).

## Tests

- `tests/unit/Web/UI/WebControls/TAudioTest.php`
- `tests/playwright/web/TMediaTagsTestCase.spec.js` with `tests/harness/web/protected/pages/MediaTagsTest.page`: attributes, captions track loading, source selection, video focus, and roles (Chromium accessibility tree)

## See Also

- [THtmlElement](./THtmlElement.md)
- [TBaseMedia](./TBaseMedia.md)
- [TVideo](./TVideo.md)
- [TMediaSource](./TMediaSource.md)

**@since 4.4.0**
