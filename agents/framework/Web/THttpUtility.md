# THttpUtility

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [THttpUtility](./THttpUtility.md)

**Location:** `framework/Web/THttpUtility.php`
**Namespace:** `Prado\Web`

## Overview

THttpUtility provides static utility methods for HTML encoding and decoding. Unlike `htmlspecialchars`, the `htmlEncode` method does not translate `&` characters.

## Key Properties/Methods

- `htmlEncode($s)` - HTML-encodes a string, translating `<`, `>`, and `"` to their entity equivalents
- `htmlDecode($s)` - Decodes HTML entities back to their characters
- `htmlStrip($s)` - Strips HTML entities completely, removing `<`, `>`, and `"`

## See Also

- [THttpResponse](./THttpResponse.md)
