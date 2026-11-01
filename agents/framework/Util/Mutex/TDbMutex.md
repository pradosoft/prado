# Util/Mutex/TDbMutex

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Mutex](./INDEX.md) / **`TDbMutex`**

## Class Info
**Location:** `framework/Util/Mutex/TDbMutex.php`
**Namespace:** `Prado\Util\Mutex`
**Extends:** [`TMutex`](TMutex.md)
**Implements:** [`IDbModule`](../IDbModule.md)
**Since:** 4.4.0

## Overview
Base of the database mutexes with `TDbPropertiesTrait` (`ConnectionID`). `getLockConnection()` returns the active connection and checks `getSupportedDrivers()` once (empty accepts any; mismatch throws `dbmutex_driver_unsupported`). `getLimitedLockKey($name, $max)` returns `KeyPrefix . name`, or its SHA-1 hash past `$max` characters. The connection activates on every retrieval; the destructor leaves it active, since other holders may share it.

## Properties

| Property | Default | Meaning |
|---|---|---|
| `ConnectionID` | '' | the `TDataSourceConfig` module |
