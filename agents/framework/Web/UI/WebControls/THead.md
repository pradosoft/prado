# Web/UI/WebControls/THead

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`THead`**

## Class Info
**Location:** `framework/Web/UI/WebControls/THead.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
THead displays a head element on a page, rendering the page title, base URL, shortcut icon, meta tags, and registered stylesheets/JavaScripts. It manages meta tags through its MetaTags collection property.

## Key Properties/Methods

- `getTitle()` / `setTitle()` - Gets or sets the page title
- `getBaseUrl()` / `setBaseUrl()` - Gets or sets base URL for the page
- `getShortcutIcon()` / `setShortcutIcon()` - Gets or sets favicon URL
- `getMetaTags()` - Returns the TMetaTagCollection
- `addParsedObject()` - Adds TMetaTag components to the collection
- `render()` - Renders the head element with all contents

## See Also

- [TPage](./TPage.md)
- [TMetaTag](./TMetaTag.md)
- [TMetaTagCollection](./TMetaTagCollection.md)
