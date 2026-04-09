# TRedisCache

### Directories
[./](../INDEX.md) > [Caching](./INDEX.md) > [TRedisCache](./TRedisCache.md)

**Location:** `framework/Caching/TRedisCache.php`
**Namespace:** `Prado\Caching`

## Overview

[TRedisCache](./TRedisCache.md) provides caching using the Redis key-value store. Supports both TCP and Unix socket connections.

## Configuration

```xml
<!-- TCP connection -->
<module id="cache" class="Prado\Caching\TRedisCache" 
        Host="localhost" Port="6379" Index="0" />

<!-- Unix socket -->
<module id="cache" class="Prado\Caching\TRedisCache" 
        Socket="/var/run/redis/redis.sock" Index="0" />
```

## Properties

- `Host` - Server hostname (default: `localhost`)
- `Port` - Server port (default: `6379`)
- `Socket` - Unix socket path (takes precedence over Host/Port)
- `Index` - Redis database index (default: `0`)

## Requirements

- PHP `redis` extension

## See Also

- [TCache](./TCache.md) for full caching documentation