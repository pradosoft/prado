# TGravatar

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TGravatar](./TGravatar.md)

**Location:** `framework/Web/UI/WebControls/TGravatar.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TGravatar extends TImage to display a Gravatar image based on an email address. It supports configurable size (1-512px), rating levels (g, pg, r, x), and default image styles including mp, identicon, monsterid, wavatar, retro, robohash, blank, 404, or custom URL.

## Key Properties/Methods

- `getImageUrl()` - Returns the complete URL to the gravatar
- `getDefaultImageStyle()` / `setDefaultImageStyle()` - Gets or sets default image style (mp, identicon, monsterid, wavatar, retro, robohash, blank, 404, or URL)
- `getSize()` / `setSize()` - Gets or sets pixel size (1-512)
- `getRating()` / `setRating()` - Gets or sets rating (g, pg, r, x)
- `getEmail()` / `setEmail()` - Gets or sets associated email address
- `getUseSecureUrl()` / `setUseSecureUrl()` - Gets or sets whether to use HTTPS URL

## See Also

- [TImage](./TImage.md)
