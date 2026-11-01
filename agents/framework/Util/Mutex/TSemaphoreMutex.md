# Util/Mutex/TSemaphoreMutex

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Mutex](./INDEX.md) / **`TSemaphoreMutex`**

## Class Info
**Location:** `framework/Util/Mutex/TSemaphoreMutex.php`
**Namespace:** `Prado\Util\Mutex`
**Extends:** [`TMutex`](TMutex.md)
**Since:** 4.4.0

## Overview
Locks with a System V semaphore of one permit: `sem_get(key, 1, Permissions, false)` and `sem_acquire($sem, true)`. The key is 31 bits of `md5(KeyPrefix . name)` (never 0, which is `IPC_PRIVATE`); names with equal keys share one lock. PHP auto-release is off so a forked child's teardown does not post the parent's permit; the kernel's `SEM_UNDO` frees the permit of a process that exits. `init()` throws `semaphoremutex_extension_required` without `sysvsem` (unavailable on Windows).

## Properties

| Property | Default | Meaning |
|---|---|---|
| `Permissions` | 0666 | permissions of a created semaphore |
