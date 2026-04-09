# THtmlWriter

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [THtmlWriter](./THtmlWriter.md)

**Location:** `framework/Web/UI/THtmlWriter.php`
**Namespace:** `Prado\Web\UI`

## Overview

THtmlWriter renders valid XHTML output, providing methods to render HTML tags with their attributes and styles. Attribute and stylesheet values are automatically HTML-encoded. THtmlWriter wraps an underlying [ITextWriter](../IO/ITextWriter.md) and maintains a stack of open tags for proper nesting.

## Key Properties/Methods

- `addAttribute($name, $value)` - Adds an HTML attribute to be rendered
- `addAttributes($attrs)` - Adds multiple HTML attributes
- `removeAttribute($name)` - Removes an attribute from rendering
- `addStyleAttribute($name, $value)` - Adds a CSS style attribute
- `addStyleAttributes($attrs)` - Adds multiple CSS style attributes
- `removeStyleAttribute($name)` - Removes a style attribute
- `renderBeginTag($tagName)` - Renders an opening tag with attributes and styles
- `renderEndTag()` - Renders the corresponding closing tag
- `write($str)` - Writes a string directly
- `writeLine($str)` - Writes a string with newline
- `writeBreak()` - Renders an HTML break tag

## See Also

- [ITextWriter](../IO/ITextWriter.md)
- [THttpUtility](../THttpUtility.md)

(End of file - total 27 lines)
