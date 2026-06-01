# Web/UI/THtmlWriter

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](./INDEX.md) / **`THtmlWriter`**

## Class Info
**Location:** `framework/Web/UI/THtmlWriter.php`
**Namespace:** `Prado\Web\UI`

## Overview
THtmlWriter renders valid XHTML/HTML5 output, providing methods to render tags with their attributes and styles. Attribute and stylesheet values are automatically HTML-encoded. THtmlWriter wraps an underlying [ITextWriter](../../IO/ITextWriter.md) and maintains a stack of open tags for proper nesting.

Void elements (those that take no closing tag in HTML5) are automatically self-closed with `/>`. The void element list is updated to the current HTML5 spec.

## Void Elements

The following tags are treated as void elements and are self-closed (`/>`):

**HTML5 spec:** `area`, `base`, `br`, `col`, `embed`, `hr`, `img`, `input`, `link`, `meta`, `source`, `track`, `wbr`

**Legacy PRADO (deprecated, will be removed in v4.4):** `basefont`, `bgsound`, `frame`, `isindex`

`getVoidElements(): array` returns all void element tag names (static method, @since 4.3.3).

## Key Properties/Methods

- `addAttribute($name, $value)` - Adds an HTML attribute to be rendered
- `addAttributes($attrs)` - Adds multiple HTML attributes
- `removeAttribute($name)` - Removes an attribute from rendering
- `addStyleAttribute($name, $value)` - Adds a CSS style attribute
- `addStyleAttributes($attrs)` - Adds multiple CSS style attributes
- `removeStyleAttribute($name)` - Removes a style attribute
- `renderBeginTag($tagName)` - Renders an opening tag with attributes and styles; void elements are immediately self-closed
- `renderEndTag()` - Renders the corresponding closing tag (no-op for void elements)
- `write($str)` - Writes a string directly
- `writeLine($str)` - Writes a string with newline
- `writeBreak()` - Renders an HTML break tag
- `static getVoidElements(): array` - Returns list of all void element tag names (@since 4.3.3)

## See Also

- [ITextWriter](../../IO/ITextWriter.md)
- [THttpUtility](../THttpUtility.md)

(End of file - total 27 lines)
