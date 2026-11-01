# Util/Mutex/TBaseDbMutex

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Mutex](./INDEX.md) / **`TBaseDbMutex`**

## Class Info
**Location:** `framework/Util/Mutex/TBaseDbMutex.php`
**Namespace:** `Prado\Util\Mutex`
**Extends:** [`TMutex`](TMutex.md)
**Implements:** [`IDbModule`](../IDbModule.md)
**Since:** 4.4.0

## Overview
Abstract base of the database mutexes with `TDbPropertiesTrait` (`ConnectionID`). `setDbConnection()` sets a connection directly, ahead of `ConnectionID`. Static `getSupportedDrivers()` and `isDriverSupported($driver)` let [`TDbMutex`](TDbMutex.md) choose a subclass without an instance. `getLockConnection()` returns the active connection and checks `getSupportedDrivers()` once (empty accepts any; mismatch throws `dbmutex_driver_unsupported`). `getLimitedLockKey($name, $max)` returns `KeyPrefix . name`, or its SHA-1 hash past `$max` characters. The connection activates on every retrieval; the destructor leaves it active, since other holders may share it.

## Properties

| Property | Default | Meaning |
|---|---|---|
| `ConnectionID` | '' | the `TDataSourceConfig` module |
| `DbConnection` | null | a connection set directly; takes precedence |
