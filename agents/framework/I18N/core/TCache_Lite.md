# I18N / core / TCache_Lite

### Directories
[./](../INDEX.md) > [I18N](../INDEX.md) > [core](./INDEX.md) > [TCache_Lite](./TCache_Lite.md)

**Location:** `framework/I18N/core/TCache_Lite.php`
**Namespace:** `Prado\I18N\core`

## Overview

Lightweight file-based cache system. Used internally by `MessageCache` as a low-dependency alternative to the full Prado caching system.

## Usage

```php
$options = [
    'cacheDir' => '/tmp/cache/',
    'lifeTime' => 3600,
    'fileLocking' => true,
    'writeControl' => true,
    'readControl' => true,
    'readControlType' => 'crc32',
];

$cache = new TCache_Lite($options);

// Store
$cache->save($data, 'myid', 'mygroup');

// Retrieve
$result = $cache->get('myid', 'mygroup');

// Remove
$cache->remove('myid', 'mygroup');
```

## Constructor Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `cacheDir` | string | `'/tmp/'` | Cache directory |
| `lifeTime` | int | `3600` | Cache TTL (seconds) |
| `fileLocking` | bool | `true` | Enable file locking |
| `writeControl` | bool | `true` | Verify writes |
| `readControl` | bool | `true` | Verify reads |
| `readControlType` | string | `'crc32'` | `'md5'`, `'crc32'`, or `'strlen'` |
| `memoryCaching` | bool | `false` | Enable memory caching |
| `automaticSerialization` | bool | `false` | Auto serialize/ unserialize |

## Key Methods

| Method | Description |
|--------|-------------|
| `get($id, $group, $doNotTestCacheValidity)` | Retrieve cached data |
| `save($data, $id, $group)` | Store data |
| `remove($id, $group)` | Remove specific entry |
| `clean($group)` | Remove all in group |
| `setLifeTime($seconds)` | Change TTL |

## Security

File names are MD5-hashed by default to prevent injection issues.

## See Also

- [MessageCache](./MessageCache.md) - Higher-level cache using TCache_Lite