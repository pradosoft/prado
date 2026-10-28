# Web/UI/WebControls/TTextTrackKind

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TTextTrackKind`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TTextTrackKind.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `TEnumerable`

## Overview
The `kind` of a [TTextTrack](./TTextTrack.md); renders lowercase.

| Constant | For |
|---|---|
| `Subtitles` | Translated dialogue, for viewers who hear the audio; default; requires `SrcLang` |
| `Captions` | Dialogue, sound effects, and speakers, for viewers who do not hear it (WCAG 1.2.2, 1.2.4) |
| `Descriptions` | Text descriptions of the video; rarely voiced by browsers |
| `Chapters` | Chapter titles for navigation |
| `Metadata` | Data for script; not shown |

**@since 4.4.0**
