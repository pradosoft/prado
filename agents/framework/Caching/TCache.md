# TCache / Cache Backends

### Directories
[./](../INDEX.md) > [Caching](./INDEX.md) > [TCache](./TCache.md)

**Location:** `framework/Caching/`
**Namespace:** `Prado\Caching`

## Overview

Abstract base [TCache](./TCache.md) defines a unified key-value cache API over multiple storage backends. Implements [ICache](./ICache.md) and `ArrayAccess`. Registered as a module in `application.xml`; accessed via `Prado::getApplication()->getCache()`.

## ICache Interface

```php
interface ICache {
    public function get($id);
    public function set($id, $value, $expire = 0, $dependency = null);
    public function add($id, $value, $expire = 0, $dependency = null);
    public function delete($id);
    public function flush();
}
```

- `$expire` — seconds; `0` = never expires
- `$dependency` — [ICacheDependency](./ICacheDependency.md) object; value is invalidated when dependency changes
- `add()` — no-op if key already exists; returns false if present

## TCache Base Class

**Template Method Pattern** — subclasses implement four protected methods:
```php
protected abstract function getValue($key): mixed;
protected abstract function setValue($key, $value, $expire): bool;
protected abstract function addValue($key, $value, $expire): bool;
protected abstract function deleteValue($key): bool;
```

**Key properties:**
- `KeyPrefix` — prepended to all cache keys to isolate apps sharing a backend. Defaults to MD5 of app unique ID.
- `PrimaryCache` — when `true` (default), registers as [TApplication](../TApplication.md)'s primary cache used by the framework itself.

## Storage Backends

### [TAPCCache](./TAPCCache.md)
- Requires: `apcu` PHP extension
- Fast single-server in-memory cache
- Data lost on PHP-FPM restart
- Config: no extra properties needed

### [TMemCache](./TMemCache.md)
- Requires: `memcached` PHP extension
- Distributed across multiple servers
- Properties: `PersistentID`, servers via `<server host="..." port="..." weight="..."/>` in XML config
- Falls back gracefully when server unavailable

### [TRedisCache](./TRedisCache.md)
- Requires: `redis` PHP extension
- Properties: `Host` (default `'localhost'`), `Port` (default `6379`), `Socket` (Unix socket), `Index` (DB 0–15), `Password`
- Uses PHP serializer (`Redis::SERIALIZER_PHP`)

### [TDbCache](./TDbCache.md)
- Requires: PDO database connection
- Properties: `ConnectionID`, `CacheTableName` (default `'pradocache'`), `AutoCreateCacheTable`, `FlushInterval`
- Supports MySQL, PostgreSQL, SQLite, Oracle, MSSQL, IBM DB2
- Auto-creates table on first use if `AutoCreateCacheTable=true`
- Registers a cron cleanup task via `fxGetCronTaskInfos()`

### [TEtcdCache](./TEtcdCache.md)
- Requires: cURL + running etcd v2
- Properties: `Host`, `Port` (default `2379`), `Dir` (etcd key prefix, default `'pradocache'`)
- Stores values as JSON with optional TTL

## Cache Dependencies

| Class | Invalidates when... |
|-------|---------------------|
| [TFileCacheDependency](./TFileCacheDependency.md) | File mtime changes |
| [TDirectoryCacheDependency](./TDirectoryCacheDependency.md) | Any file in directory changes; `RecursiveCheck`, `RecursiveLevel` properties |
| [TGlobalStateCacheDependency](./TGlobalStateCacheDependency.md) | Named Prado global state changes |
| [TApplicationStateCacheDependency](./TApplicationStateCacheDependency.md) | App is NOT in `Performance` mode |
| [TChainedCacheDependency](./TChainedCacheDependency.md) | **Any** child dependency reports changed (OR logic) |
| [TCacheDependencyList](./TCacheDependencyList.md) | Typed [TList](../Collections/TList.md) of [ICacheDependency](./ICacheDependency.md) objects |

## Configuration (application.xml)

```xml
<!-- Primary cache using Redis: -->
<module id="cache" class="Prado\Caching\TRedisCache" Host="localhost" Port="6379"/>

<!-- Database cache: -->
<module id="cache" class="Prado\Caching\TDbCache" ConnectionID="db" AutoCreateCacheTable="true"/>
```

## ArrayAccess Usage

```php
$cache = $app->getCache();
$cache['mykey'] = $value;          // set with no TTL
$value = $cache['mykey'];          // get
unset($cache['mykey']);            // delete
isset($cache['mykey']);            // check existence
```

## Patterns & Gotchas

- **Only one primary cache** — `PrimaryCache=true` on two modules throws `TConfigurationException`.
- **`add()` vs `set()`** — `add()` is a conditional insert; `set()` always overwrites.
- **Dependency serialization** — dependency objects are serialized alongside the cached value. Keep them small and serializable.
- **`TDbCache` cleanup** — expired entries are NOT purged on every read; cleanup runs on configurable `FlushInterval` or via cron task.
- **Key prefix** — two Prado apps sharing the same Redis/Memcached are isolated by default. Override `KeyPrefix` for intentional sharing.
