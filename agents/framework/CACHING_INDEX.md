# Caching/INDEX.md - CACHING_INDEX.md

This file provides guidance to Agents when working with code in this repository.

## Purpose

Cache backends and cache dependency system for the Prado framework. Provides a unified `ICache` interface over multiple storage backends (memory, database, distributed key-value stores).

## Interfaces

- **`ICache`** — Core cache contract. Methods: `get($id)`, `set($id, $value, $expire, $dependency)`, `add($id, $value, $expire, $dependency)`, `delete($id)`, `flush()`. All implementations must honour the `$expire` TTL (seconds; `0` = never) and invalidate entries when a dependency changes.

- **`ICacheDependency`** — Marker interface for dependency objects. Single method: `getHasChanged()` → bool. Must be serialisable (stored alongside the cached value).

## Base Classes

- **`TCache`** — Abstract base implementing `ICache` and `ArrayAccess`. Provides:
  - Unified `get()`/`set()`/`add()`/`delete()` with dependency checking and key prefixing.
  - Key prefix defaults to an MD5 hash of the application unique ID (prevents collisions when multiple apps share one backend).
  - `PrimaryCache` property — when `true`, registers this cache as `TApplication`'s primary cache.
  - Subclasses implement four abstract methods: `getValue($key)`, `setValue($key, $value, $expire)`, `addValue($key, $value, $expire)`, `deleteValue($key)`.
  - `ArrayAccess` support: `$cache['key']` calls `get()`; `$cache['key'] = $value` calls `set()` with no TTL.

- **`TCacheDependency`** — Abstract base for all dependencies; extends `TComponent`.

## Storage Backends

- **`TAPCCache`** — APCu in-memory cache. Requires `apcu` PHP extension. Fast but single-server only; data lost on PHP-FPM restart. Wraps `apcu_fetch()`, `apcu_store()`, `apcu_add()`, `apcu_delete()`.

- **`TMemCache`** — Memcached distributed cache. Uses the `\Memcached` PHP extension. Supports multiple servers with `<server>` child elements in XML config (Host, Port, Weight). Property: `PersistentID` for cross-request connection pooling.

- **`TRedisCache`** — Redis distributed cache. Uses the `\Redis` PHP extension. Properties: `Host`, `Port` (default `6379`), `Socket` (Unix socket), `Index` (DB 0–15). Uses PHP serialiser (`Redis::SERIALIZER_PHP`).

- **`TDbCache`** — PDO-backed persistent cache. Auto-creates a `pradocache` table if `AutoCreateCacheTable=true`. Supports MySQL, PostgreSQL, SQLite, Oracle, MSSQL, IBM DB2 (each with driver-specific upsert syntax). Properties: `ConnectionID`, `ConnectionString`, `CacheTableName`, `FlushInterval`. Registers a cron task via `fxGetCronTaskInfos()` for periodic expired-entry cleanup.

- **`TEtcdCache`** — etcd distributed cache using the HTTP v2 API via cURL. Properties: `Host`, `Port` (default `2379`), `Dir` (etcd directory, default `'pradocache'`). Values stored as JSON with optional TTL.

## Cache Dependencies

- **`TFileCacheDependency`** — Invalidates when a file's `mtime` changes.
- **`TDirectoryCacheDependency`** — Invalidates when any file in a directory changes. Properties: `RecursiveCheck`, `RecursiveLevel`. Override `validateFile()`/`validateDirectory()` for custom filtering.
- **`TGlobalStateCacheDependency`** — Invalidates when a named Prado global state variable changes.
- **`TApplicationStateCacheDependency`** — Invalidates unless the application is in `Performance` mode (useful for cache bypass in `Debug` mode).
- **`TChainedCacheDependency`** — Composite dependency: invalidates if **any** child dependency reports changed (OR logic). Child list is a `TCacheDependencyList`.
- **`TCacheDependencyList`** — Typed `TList` that enforces `ICacheDependency` on all items.

## Patterns & Gotchas

- **Template Method pattern** — `TCache` defines the public API flow; subclasses only implement the four backend-specific protected methods.
- **Serialisation** — Values and dependencies are serialised together before storage. Ensure all objects stored in cache are serialisable.
- **`add()` vs `set()`** — `add()` is a conditional set (no-op if the key already exists); `set()` always overwrites.
- **Key prefix** — Two apps sharing the same Memcached/Redis instance are isolated by default via the key prefix. Override `KeyPrefix` if intentional sharing is needed.
- **`TDbCache` cleanup** — Expired entries are cleaned on a configurable interval (`FlushInterval`), not on every read. For aggressive cleanup, register the cron task explicitly.
