# Util/Mutex/IMutex

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Mutex](./INDEX.md) / **`IMutex`**

## Class Info
**Location:** `framework/Util/Mutex/IMutex.php`
**Namespace:** `Prado\Util\Mutex`
**Type:** interface
**Since:** 4.4.0

## Overview
The contract of a named lock. `acquire(string $name, float|int $timeout = 0): bool` takes the lock, waiting up to `$timeout` seconds; `release(string $name): bool` gives back a lock this mutex holds; `isAcquired(string $name): bool` reports whether this mutex holds it.
