# THttpCookieCollection

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [THttpCookieCollection](./THttpCookieCollection.md)

**Location:** `framework/Web/THttpCookieCollection.php`
**Namespace:** `Prado\Web`

## Overview

THttpCookieCollection implements a collection class for storing cookies. It extends [TList](../../Collections/TList.md) and allows cookie retrieval by name. When cookies are added or removed from the collection, they are automatically added to or removed from the owning THttpResponse.

## Key Properties/Methods

- `insertAt($index, $item)` - Inserts a THttpCookie at the specified position
- `removeAt($index)` - Removes a cookie at the specified position
- `itemAt($index)` - Returns the cookie at the index or by name
- `findCookieByName($name)` - Finds a cookie by its name

## See Also

- [THttpCookie](./THttpCookie.md)
- [THttpResponse](./THttpResponse.md)
