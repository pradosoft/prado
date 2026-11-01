# Util/Mutex/TRedisMutex

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Mutex](./INDEX.md) / **`TRedisMutex`**

## Class Info
**Location:** `framework/Util/Mutex/TRedisMutex.php`
**Namespace:** `Prado\Util\Mutex`
**Extends:** [`TMutex`](TMutex.md)
**Since:** 4.4.0

## Overview
Locks with `SET mutex:<KeyPrefix><name> token NX PX <Expire ms>` and releases with `RELEASE_SCRIPT`, a Lua script that deletes the key only when it holds the token. `init()` connects (Socket over Host/Port) and selects `Index`; it throws `redismutex_extension_required` without the `redis` extension. `newRedis()` is the seam a test overrides. Connection properties are fixed after `init()` (`TInitializedTrait`).

## Properties

| Property | Default | Meaning |
|---|---|---|
| `Host` | 'localhost' | server host |
| `Port` | 6379 | server port |
| `Socket` | null | Unix socket; takes precedence |
| `Index` | 0 | database index |
| `Expire` | 30.0 | lease seconds, to the millisecond |
