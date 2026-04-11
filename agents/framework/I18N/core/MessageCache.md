# I18N/core/MessageCache

### Directories
[framework](./INDEX.md) / [I18N](./I18N/INDEX.md) / [core](./I18N/core/INDEX.md) / **`MessageCache`**

**Location:** `framework/I18N/core/MessageCache.php`
**Namespace:** `Prado\I18N\core`

## Overview
Filesystem cache for compiled message catalogues. Stores serialized translation arrays and auto-invalidates when source file `mtime` changes.

## Usage

```php
$cache = new MessageCache('/tmp/prado/cache');
$cache->setLifeTime(3600);  // 1 hour TTL

// Get cached messages
$data = $cache->get('messages', 'en_US', filemtime('/path/to/messages.mo'));

// Save to cache
$cache->save($translationData, 'messages', 'en_US');
```

## Constructor

```php
public function __construct(string $cacheDir)
```

Creates cache in the specified directory. Directory must exist and be writable.

## Key Methods

| Method | Description |
|--------|-------------|
| `get($catalogue, $culture, $lastmodified = 0)` | Get cached data if not expired |
| `save($data, $catalogue, $culture)` | Save translation data |
| `clean($catalogue, $culture)` | Remove cache for specific catalogue |
| `clear()` | Flush all cache |
| `getLifeTime(): int` | Get cache TTL |
| `setLifeTime(int $time)` | Set cache TTL (seconds) |

## Cache Invalidation

Cache is automatically invalidated when the source file's modification time is newer than the cached version. Pass `filemtime()` as `$lastmodified` to `get()`.

## See Also

- [TCache_Lite](./TCache_Lite.md) - Lightweight cache layer used internally
- [MessageSource](./MessageSource.md) - Uses MessageCache for caching
