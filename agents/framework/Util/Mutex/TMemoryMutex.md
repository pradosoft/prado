# Util/Mutex/TMemoryMutex

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Mutex](./INDEX.md) / **`TMemoryMutex`**

## Class Info
**Location:** `framework/Util/Mutex/TMemoryMutex.php`
**Namespace:** `Prado\Util\Mutex`
**Extends:** [`TMutex`](TMutex.md)
**Since:** 4.4.0

## Overview
Locks in a static table shared by every `TMemoryMutex` of the process. Excludes the other mutexes and fibers of the process; another process is not excluded. `forgetLock()` frees the child's copy of the parent's names. Used for fiber coordination and for tests of code that takes an [`IMutex`](IMutex.md).
