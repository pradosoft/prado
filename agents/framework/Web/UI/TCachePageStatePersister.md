# Web/UI/TCachePageStatePersister

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](./INDEX.md) / **`TCachePageStatePersister`**

## Class Info
**Location:** `framework/Web/UI/TCachePageStatePersister.php`
**Namespace:** `Prado\Web\UI`

## Overview
TCachePageStatePersister implements page state persistence using a cache backend (memcache, DB, APC, etc.). Only a small token is passed to the client, reducing page state transmission size. The cache timeout limits how long state data is stored. Requires a cache module to be loaded.

## Key Properties/Methods

- `Page` - The page this persister works for
- `CacheModuleID` - The ID of the cache module to use
- `Cache` - The ICache instance being used
- `CacheTimeout` - Seconds before cached state expires (default 1800); the fallback at the end of every `CacheTimeoutMode` chain
- `CacheTimeoutMode` - Where the lifetime of a state saved now comes from: `Fixed` (default), `Session`, `Auth` or `Auto`; see [TCachePageStatePersisterTimeoutMode](./TCachePageStatePersisterTimeoutMode.md) (4.4.0)
- `EffectiveCacheTimeout` - The lifetime `save()` passes to the cache, resolved through `CacheTimeoutMode` (4.4.0)
- `KeyPrefix` - Prefix for cache keys (default 'statepersister')
- `Compression` - [TPageStateCompressionConfig](./TPageStateCompressionConfig.md) the client token is written under, from `TCompressionConfigTrait`; the cached state is stored as is (4.4.0)
- `save($data)` - Saves state to cache
- `load()` - Loads state from cache, throws THttpException if corrupted

## Lifetime

A cached page state is needed for as long as its page can still be posted back. A lifetime shorter than the user's login turns a postback into a 400 "page state corrupted". `save()` resolves the lifetime when it writes the entry, since `AuthExpire` slides forward on each request.

- `getAuthTimeout()` returns `TAuthManager::AuthExpire` for an authenticated user when it is above 0 and `AllowAutoLogin` is off, else 0. `AuthExpire` 0 means "never"; passing it through would make every cached state permanent, so it falls through instead.
- `getSessionTimeout()` returns the session module's `Timeout` (`session.gc_maxlifetime`), else 0.
- Both find modules through `getModulesByType()`. `TApplication::getSession()` would bootstrap a default session module as a side effect.
- Both are protected so a subclass or test can supply the lifetimes.
- `CacheTimeout` 0 has always meant "never expires" at the cache. `Fixed` passes it through silently, as before 4.4.0. Any other mode that falls all the way through to a `CacheTimeout` of 0 logs a `TLogger::WARNING` under the `TCachePageStatePersister` category, since a lifetime was asked for and the state is cached without one. It does not throw: 0 is an accepted value on the setter and the cache module.

## See Also

- [TPageStateFormatter](./TPageStateFormatter.md)
- [ICache](../../Caching/ICache.md)
- [IPageStatePersister](./IPageStatePersister.md)

(End of file - total 24 lines)
