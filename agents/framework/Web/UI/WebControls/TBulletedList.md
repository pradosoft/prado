# Web/UI/WebControls/TBulletedList

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TBulletedList`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TBulletedList.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TBulletedList displays items in a bullet format with configurable bullet styles (disc, circle, square, numbered, alphabetical, roman numerals) and display modes (text, hyperlink, linkbutton).

## Key Properties/Methods

- `BulletStyle` - Style of bullets (TBulletStyle)
- `BulletImageUrl` - Custom image for bullets when style is CustomImage
- `DisplayMode` - How items are displayed (Text, HyperLink, LinkButton)
- `FirstBulletNumber` - Starting index for numbered lists
- `Target` - Target window for hyperlinks
- `onClick()` - Event raised when LinkButton item clicked
- `AccessKey` - In the HyperLink and LinkButton modes, rendered on the first enabled item link only (`addAccessKeyToRender()`), not on the list; in Text mode, on the list

## See Also

- [TBulletStyle](./TBulletStyle.md)
- [TBulletedListDisplayMode](./TBulletedListDisplayMode.md)
