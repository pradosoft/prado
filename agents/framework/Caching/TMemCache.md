# Caching/TMemCache

### Directories
[framework](../INDEX.md) / [Caching](./INDEX.md) / **`TMemCache`**

## Class Info
**Location:** `framework/Caching/TMemCache.php`
**Namespace:** `Prado\Caching`

## Overview
[TMemCache](./TMemCache.md) provides distributed caching using the memcached server. Supports multiple servers with weighted distribution.

## Configuration

```xml
<!-- Single server -->
<module id="cache" class="Prado\Caching\TMemCache" 
        Host="localhost" Port="11211" />

<!-- Multiple servers -->
<module id="cache" class="Prado\Caching\TMemCache">
    <server Host="192.168.1.100" Port="11211" Weight="1" />
    <server Host="192.168.1.101" Port="11211" Weight="2" />
</module>
```

## Properties

- `Host` - Server hostname (default: `localhost`)
- `Port` - Server port (default: `11211`)
- `PersistentID` - Memcached persistent connection ID
- `Options` - Memcached options array

## Requirements

- PHP `memcached` extension (not the older `memcache`)

## See Also

- [TCache](./TCache.md) for full caching documentation