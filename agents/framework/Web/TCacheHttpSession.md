# Web/TCacheHttpSession

### Directories
[framework](../INDEX.md) / [Web](./INDEX.md) / **`TCacheHttpSession`**

## Class Info
**Location:** `framework/Web/TCacheHttpSession.php`
**Namespace:** `Prado\Web`

## Overview
TCacheHttpSession extends THttpSession and provides session storage using a cache module (e.g., [TMemCache](../../Caching/TMemCache.md), [TDbCache](../../Caching/TDbCache.md)). This enables distributed session management across multiple servers.

## Key Properties/Methods

- `getCacheModuleID()` / `setCacheModuleID($value)` - Gets/sets the cache module ID
- `getCache()` - Returns the ICache module being used
- `getKeyPrefix()` / `setKeyPrefix($value)` - Gets/sets the cache key prefix (default: 'session')
- `_read($id)` - Reads session data from cache
- `_write($id, $data)` - Writes session data to cache
- `_destroy($id)` - Destroys session data from cache

## See Also

- [THttpSession](./THttpSession.md)
- [THttpSessionHandler](./THttpSessionHandler.md)
