# Web/UI/WebControls/THead

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`THead`**

## Class Info
**Location:** `framework/Web/UI/WebControls/THead.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
THead displays a head element on a page, rendering the page title, base URL, shortcut icon, meta tags, link tags, and registered stylesheets/JavaScripts, in that order. It manages meta tags through its MetaTags collection property and `<link>` elements through its LinkTags collection (4.4.0).

## Key Properties/Methods

- `getTitle()` / `setTitle()` - Gets or sets the page title
- `getBaseUrl()` / `setBaseUrl()` - Gets or sets base URL for the page
- `getShortcutIcon()` / `setShortcutIcon()` - Gets or sets favicon URL
- `getMetaTags()` - Returns the TMetaTagCollection
- `getLinkTags()` - Returns the TLinkTagCollection (4.4.0)
- `addParsedObject()` - Adds TMetaTag and TLinkTag components to their collections
- `render()` - Renders the head element with all contents

## See Also

- [TPage](../TPage.md)
- [TMetaTag](./TMetaTag.md)
- [TMetaTagCollection](./TMetaTagCollection.md)
- [TLinkTag](./TLinkTag.md)
- [TLinkTagCollection](./TLinkTagCollection.md)
