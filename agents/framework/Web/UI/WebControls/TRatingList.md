# Web/UI/WebControls/TRatingList

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TRatingList`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TRatingList.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TRatingList displays a star-based rating control that allows users to select a rating. It extends TRadioButtonList and is experimental. The control supports half-star ratings, read-only mode, and integrates with client-side JavaScript for interactive rating selection.

## Key Properties/Methods

- `getReadOnly()` / `setReadOnly(bool)` - Whether the rating can be edited
- `getRating()` / `setRating(float)` - Gets or sets the current rating value
- `getHalfRatingInterval()` / `setHalfRatingInterval(array)` - Interval for half-star display (default: [0.3, 0.7])
- `getCaptionID()` / `setCaptionID(string)` - Control ID for displaying a caption
- `getRatingStyle()` / `setRatingStyle(string)` - CSS style name for rating images
- `onSelectedIndexChanged($param)` - Event raised when rating changes
- `addGroupAttributesToRender()` - Adds `aria-readonly="true"` to the radiogroup table when read-only

## Accessibility

- The list renders as a `role="radiogroup"` table; each star cell holds a native radio.
- The radios are visually hidden (clip), not `display:none`, so they stay focusable and in the accessibility tree.
- `ratings.js` sets each radio's `aria-label` from its item text, or its 1-based position when the text is empty.
- The star image is a CSS background, outside the accessibility tree. The star cells carry no `aria-hidden`, which would hide the focusable radio inside them (WCAG 4.1.2).
- Read-only disables the radios and toggles `aria-readonly` on the group.

## See Also

- [TRadioButtonList](./TRadioButtonList.md)
- [TBaseValidator](./TBaseValidator.md)
