# Web/UI/WebControls/THyperLink

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`THyperLink`**

## Class Info
**Location:** `framework/Web/UI/WebControls/THyperLink.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
THyperLink displays a hyperlink on a page. Can display text or an image, with configurable URL and target.

## Key Properties/Methods

- `Text` - Link text caption
- `NavigateUrl` - URL to navigate to
- `Target` - Target window/frame
- `ImageUrl` - Image to display instead of text
- `ImageStyle` - Style for the image
- `Data` - Implements IDataRenderer (same as Text)

## Link Attributes (@since 4.4.0)

| Property | Attribute | Notes |
|---|---|---|
| `Download` | `download` | null: navigate; '' or true: save under the resource name; a file name. Same-origin, `blob:`, and `data:` URLs only. |
| `Rel` | `rel` | link types, such as `nofollow`, `noopener noreferrer` |
| `HrefLang` | `hreflang` | BCP 47 tag |
| `Type` | `type` | advisory MIME type |
| `ReferrerPolicy` | `referrerpolicy` | `TReferrerPolicy` |
| `Ping` | `ping` | space-separated URLs; Firefox disables it |

`addLinkAttributesToRender()` renders them only with the `href`, so a disabled link or one without `NavigateUrl` has none (HTML forbids them without `href`).

## See Also

- [TWebControl](./TWebControl.md)
