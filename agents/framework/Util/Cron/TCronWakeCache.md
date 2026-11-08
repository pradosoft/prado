# Util/Cron/TCronWakeCache

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TCronWakeCache`**

## Class Info
**Location:** `framework/Util/Cron/TCronWakeCache.php`
**Namespace:** `Prado\Util\Cron`
**Extends:** [`TCronWakePoll`](TCronWakePoll.md)
**Since:** 4.4.0

## Overview
`TCronWakeCache` signals through a cache: a wake stores the current microtime under `CacheKey`, and the waiting cron process polls the key every `PollInterval`. A cache that several hosts share, such as memcached or Redis, carries the wake between hosts. The cache is the `CacheID` module, or the application cache when none is set; a `TMemoryCache` reaches only its own process.

```xml
<module id="cronwake" class="Prado\Util\Cron\TCronWakeCache" CacheID="memcache" PollInterval="0.5" />
<module id="cron" class="Prado\Util\Cron\TDbCronManager" WakeTransport="cronwake" ... />
```

## Properties

| Property | Default | Description |
|---|---|---|
| `CacheID` | `null` | ID of the `ICache` module; null uses the application cache. An ID of another kind of module throws `cronwakecache_cacheid_invalid`. |
| `Cache` | resolved | The `ICache` instance; settable directly. |
| `CacheKey` | `prado:cron:wake` | The cache key of the stamp. |
| `PollInterval` | `1.0` | Seconds between polls. |

## See Also

- [TCronWakePoll](TCronWakePoll.md)
- [ICronWakeTransport](ICronWakeTransport.md)
