# Caching/SUMMARY.md

Cache backends and dependency system providing unified `ICache` interface over multiple storage backends.

## Classes

- **`ICache`** — Core cache contract; methods: `get($id)`, `set($id, $value, $expire, $dependency)`, `add($id, $value, $expire, $dependency)`, `delete($id)`, `flush()`.

- **`TCache`** — Abstract base implementing `ICache` and `ArrayAccess`; provides unified `get()`/`set()`/`add()`/`delete()` with dependency checking and key prefixing.

- **`TCacheDependency`** — Abstract base for all cache dependencies; extends `TComponent`.

- **`TAPCCache`** — APCu in-memory cache using `apcu_fetch()`, `apcu_store()`, `apcu_add()`, `apcu_delete()`.

- **`TMemCache`** — Memcached distributed cache using `\Memcached` PHP extension; supports multiple servers.

- **`TRedisCache`** — Redis distributed cache using `\Redis` PHP extension; properties: `Host`, `Port`, `Socket`, `Index`.

- **`TDbCache`** — PDO-backed persistent cache with auto-created `pradocache` table.

- **`TEtcdCache`** — etcd distributed cache using HTTP v2 API via cURL.

- **`TFileCacheDependency`** — Invalidates when a file's `mtime` changes.

- **`TDirectoryCacheDependency`** — Invalidates when any file in a directory changes; properties: `RecursiveCheck`, `RecursiveLevel`.

- **`TGlobalStateCacheDependency`** — Invalidates when a named Prado global state variable changes.

- **`TApplicationStateCacheDependency`** — Invalidates unless the application is in `Performance` mode.

- **`TChainedCacheDependency`** — Composite dependency that invalidates if any child dependency reports changed.

- **`TCacheDependencyList`** — Typed `TList` enforcing `ICacheDependency` on all items.
