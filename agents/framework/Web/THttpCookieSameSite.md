# THttpCookieSameSite

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [THttpCookieSameSite](./THttpCookieSameSite.md)

**Location:** `framework/Web/THttpCookieSameSite.php`
**Namespace:** `Prado\Web`

## Overview

THttpCookieSameSite defines the enumerable type for the SameSite cookie attribute. This attribute mitigates CSRF attacks by controlling when cookies are sent with cross-site requests. Requires PHP 7.3.0+.

## Key Properties/Methods

- `Lax` - Cookies sent with top-level navigations and GET requests from third-party sites
- `Strict` - Cookies sent only in first-party context, never with third-party requests
- `None` - Cookies sent in all contexts, including cross-site requests (requires Secure flag)

## See Also

- [THttpCookie](./THttpCookie.md)
