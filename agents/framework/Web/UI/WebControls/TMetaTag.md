# Web/UI/WebControls/TMetaTag

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TMetaTag`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TMetaTag.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TMetaTag represents a meta tag appearing in a page head section. Supports http-equiv, name, property, content, charset, and scheme attributes. The RDFa `property` attribute names Open Graph metadata, which [TOpenGraph](./TOpenGraph.md) renders.

## Key Properties/Methods

- `getID()` / `setID()` - Gets or sets the meta tag ID
- `getHttpEquiv()` / `setHttpEquiv()` - Gets or sets http-equiv attribute
- `getName()` / `setName()` - Gets or sets name attribute
- `getProperty()` / `setProperty()` - Gets or sets the RDFa property attribute, such as `og:title` (4.4.0)
- `getContent()` / `setContent()` - Gets or sets content attribute
- `getScheme()` / `setScheme()` - Gets or sets scheme attribute (deprecated)
- `getCharset()` / `setCharset()` - Gets or sets charset attribute
- `render()` - Renders id, name, property, http-equiv, scheme, and charset through their getters, skipping empty values; a tag without a charset also renders `content`, even when empty

Setters store strings (`TPropertyValue::ensureString()`), so null is empty.

## Tests

- `tests/unit/Web/UI/WebControls/TMetaTagTest.php`

## See Also

- [THead](./THead.md)
- [TMetaTagCollection](./TMetaTagCollection.md)
- [TOpenGraph](./TOpenGraph.md)
