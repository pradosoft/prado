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
<modules>
    <!-- Single server -->
    <module id="cache" class="Prado\Caching\TMemCache" 
            Host="localhost" Port="11211" />

    <!-- Multiple servers -->
    <module id="cache" class="Prado\Caching\TMemCache">
        <server Host="192.168.1.100" Port="11211" Weight="1" />
        <server Host="192.168.1.101" Port="11211" Weight="2" />
    </module>
</modules>
```

**PHP equivalent:**
```php
return [
    'modules' => [
        'cache' => [
            'class' => 'Prado\Caching\TMemCache',
            'properties' => ['Host' => 'localhost', 'Port' => '11211'],
        ],
    ],
];
```

## Properties

- `Host` - Server hostname (default: `localhost`); must be set before `init()`
- `Port` - Server port (default: `11211`); must be set before `init()`
- `PersistentID` - Memcached persistent connection ID; all instances sharing the same ID share one connection pool
- `Options` - Memcached options array (see [Memcached constants](https://www.php.net/manual/en/memcached.constants.php)); **must be set after `init()`** — throws `TInvalidOperationException` if called before initialization

## Persistent Connections

When `PersistentID` is set, the `Memcached` instance persists between requests. If the persistent instance already has servers in its list, `init()` skips re-adding them (logged via trace).

## Requirements

- PHP `memcached` extension (not the older `memcache`)
- `UseMemcached` property is deprecated since Prado 4.1 — only `memcached` is supported; setting it to `false` throws `TInvalidOperationException`

## See Also

- [TCache](./TCache.md) for full caching documentation