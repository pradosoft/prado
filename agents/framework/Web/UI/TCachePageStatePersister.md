# TCachePageStatePersister

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [TCachePageStatePersister](./TCachePageStatePersister.md)

**Location:** `framework/Web/UI/TCachePageStatePersister.php`
**Namespace:** `Prado\Web\UI`

## Overview

TCachePageStatePersister implements page state persistence using a cache backend (memcache, DB, APC, etc.). Only a small token is passed to the client, reducing page state transmission size. The cache timeout limits how long state data is stored. Requires a cache module to be loaded.

## Key Properties/Methods

- `Page` - The page this persister works for
- `CacheModuleID` - The ID of the cache module to use
- `Cache` - The ICache instance being used
- `CacheTimeout` - Seconds before cached state expires (default 1800)
- `KeyPrefix` - Prefix for cache keys (default 'statepersister')
- `save($data)` - Saves state to cache
- `load()` - Loads state from cache, throws THttpException if corrupted

## See Also

- [TPageStateFormatter](./TPageStateFormatter.md)
- [ICache](../Caching/ICache.md)
- [IPageStatePersister](./IPageStatePersister.md)

(End of file - total 24 lines)
