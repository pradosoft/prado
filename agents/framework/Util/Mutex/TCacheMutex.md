# Util/Mutex/TCacheMutex

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Mutex](./INDEX.md) / **`TCacheMutex`**

## Class Info
**Location:** `framework/Util/Mutex/TCacheMutex.php`
**Namespace:** `Prado\Util\Mutex`
**Extends:** [`TMutex`](TMutex.md)
**Since:** 4.4.0

## Overview
Locks with `ICache::add('mutex:' . KeyPrefix . name, token, Expire)`, where the token is 32 random hex digits. Release reads the entry and deletes it only when it holds this mutex's token; the read and delete are two calls. The cache is `Cache` when set, else the module `CacheModuleID` names, else the primary cache ([`TCacheModuleIDTrait`](../../Caching/TCacheModuleIDTrait.md)). `add()` is atomic on TAPCCache, TMemCache, TRedisCache, and TEtcdCache; TFileCache and TDbCache leave a race window.

## Properties

| Property | Default | Meaning |
|---|---|---|
| `CacheModuleID` | '' | the cache module; empty for the primary cache |
| `Cache` | null | an `ICache` set directly |
| `Expire` | 30 | lease seconds; at least 1 (`mutex_expire_invalid`) |
