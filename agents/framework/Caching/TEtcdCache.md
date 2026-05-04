# Caching/TEtcdCache

### Directories
[framework](../INDEX.md) / [Caching](./INDEX.md) / **`TEtcdCache`**

## Class Info
**Location:** `framework/Caching/TEtcdCache.php`
**Namespace:** `Prado\Caching`

## Overview
[TEtcdCache](./TEtcdCache.md) provides caching using the etcd distributed key-value store via HTTP API v2.

## Configuration

```xml
<module id="cache" class="Prado\Caching\TEtcdCache" 
        Host="localhost" Port="2379" Dir="pradocache" />
```

## Properties

- `Host` - etcd server host (default: `localhost`)
- `Port` - etcd server port (default: `2379`)
- `Dir` - Key prefix directory (default: `pradocache`)

## Requirements

- PHP cURL extension
- etcd v2 server running

## See Also

- [TCache](./TCache.md) for full caching documentation