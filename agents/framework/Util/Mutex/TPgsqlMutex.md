# Util/Mutex/TPgsqlMutex

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Mutex](./INDEX.md) / **`TPgsqlMutex`**

## Class Info
**Location:** `framework/Util/Mutex/TPgsqlMutex.php`
**Namespace:** `Prado\Util\Mutex`
**Extends:** [`TBaseDbMutex`](TBaseDbMutex.md)
**Since:** 4.4.0

## Overview
`SELECT pg_try_advisory_lock(:key1, :key2)` and `pg_advisory_unlock()`. `getAdvisoryKeys()` takes the first two big-endian 32-bit words of `sha1(KeyPrefix . name)` as signed `int4`. The two-key space does not collide with single `bigint` advisory locks. Driver `pgsql`.
