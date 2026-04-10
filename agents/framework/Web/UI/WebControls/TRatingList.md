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

## See Also

- [TRadioButtonList](./TRadioButtonList.md)
- [TBaseValidator](./TBaseValidator.md)
