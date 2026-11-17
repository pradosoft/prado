# Web/THttpUtility

### Directories
[framework](../INDEX.md) / [Web](./INDEX.md) / **`THttpUtility`**

## Class Info
**Location:** `framework/Web/THttpUtility.php`
**Namespace:** `Prado\Web`

## Overview
THttpUtility provides static utility methods for HTML encoding and decoding. Unlike `htmlspecialchars`, the `htmlEncode` method does not translate `&` characters.

## Key Properties/Methods

- `htmlEncode($s)` - HTML-encodes a string, translating `<`, `>`, and `"` to their entity equivalents
- `htmlDecode($s)` - Decodes HTML entities back to their characters
- `htmlStrip($s)` - Strips HTML entities completely, removing `<`, `>`, and `"`
- `isLocalUrl($url, $matchSubdomains = false, $serverName = null)` (4.4.0) - Whether a URL loads from the current server: a relative path is local; an absolute or protocol-relative (`//host`, or `/\host` and `\\host` as browsers read them) URL is local when its host is the server name, or a subdomain of it with `$matchSubdomains`. The SRI helpers emit `integrity` only for a URL that is not local.

## See Also

- [THttpResponse](./THttpResponse.md)
