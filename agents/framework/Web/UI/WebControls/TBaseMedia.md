# Web/UI/WebControls/TBaseMedia

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TBaseMedia`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TBaseMedia.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement` (abstract)

## Overview
TBaseMedia is the base class of [TVideo](./TVideo.md) and [TAudio](./TAudio.md). `Src` is the media URL, or child [TMediaSource](./TMediaSource.md) controls give alternatives. Children render in order: sources, [TTextTrack](./TTextTrack.md) tracks, then fallback content (shown when the browser cannot play the media, such as a download link).

## Key Properties

| Property | Attribute | Default |
|---|---|---|
| `Src` | `src` | `''` |
| `ShowControls` | `controls` | **true** (HTML's default is off) |
| `AutoPlay` | `autoplay` | false |
| `Loop` | `loop` | false |
| `Muted` | `muted` | false |
| `Preload` | `preload` ([TMediaPreload](./TMediaPreload.md)) | NotSet (not rendered) |
| `CrossOrigin` | `crossorigin` | `''`; `anonymous` or `use-credentials`; another value throws `media_crossorigin_invalid` |

The property is `ShowControls` because `TControl::getControls()` is the child collection. Boolean attributes render as `name="name"`.

## Accessibility

| Rule | Where it is met |
|---|---|
| Keyboard and screen reader access to play, pause, volume, captions | The browser's controls: `ShowControls` defaults to true. Set false only with labeled page controls |
| Captions for recorded video with sound (WCAG 1.2.2) | A `Captions` TTextTrack |
| Transcript for recorded audio (WCAG 1.2.1) | Page text or a link |
| Audio description (WCAG 1.2.3, 1.2.5) | A described version or a transcript; `Descriptions` tracks are rarely voiced |
| Stop sound that plays over 3 s on its own (WCAG 1.4.2) | The browser's controls |
| Pause motion over 5 s (WCAG 2.2.2) | The browser's controls; `AutoPlay` + `Loop` without ShowControls needs a page pause control |

Browsers block `AutoPlay` with sound unless `Muted`.

## Tests

- `tests/unit/Web/UI/WebControls/TVideoTest.php`, `TAudioTest.php`

**@since 4.4.0**
