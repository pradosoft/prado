# Util/Mutex/TMutexException

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Mutex](./INDEX.md) / **`TMutexException`**

## Class Info
**Location:** `framework/Util/Mutex/TMutexException.php`
**Namespace:** `Prado\Util\Mutex`
**Extends:** `Prado\Exceptions\TSystemException`
**Since:** 4.4.0

## Overview
Thrown by `TMutex::synchronize()` when the lock is not acquired within the timeout (`mutex_acquire_failed`, with the class, name, and timeout).
