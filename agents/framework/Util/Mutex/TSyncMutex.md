# Util/Mutex/TSyncMutex

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Mutex](./INDEX.md) / **`TSyncMutex`**

## Class Info
**Location:** `framework/Util/Mutex/TSyncMutex.php`
**Namespace:** `Prado\Util\Mutex`
**Extends:** [`TMutex`](TMutex.md)
**Since:** 4.4.0

## Overview
Locks with `new SyncSemaphore(name, 1, false)` and `lock(0)` from the PECL `sync` extension (1.1.3); release is `unlock()`. It excludes the processes of one host on Windows (a Win32 named semaphore) and POSIX (a pthread semaphore in named shared memory, whose name the extension hashes to the system limit). The name is `Prado_Mutex_` + `md5(KeyPrefix . name)`, prefixed `Global\` on Windows when `GlobalNamespace` is true, so services and users in different sessions share it.

Auto-unlock is off, so freeing the object never posts a permit; `SyncMutex` is not used because its free handler always unlocks. On POSIX the extension keeps an open-object reference count in the shared memory, and the first object opened at a count of zero resets the semaphore. A forked child's inherited object lowers the count at the child's exit without having raised it, so after a fork while holding, a later process can reset the semaphore and take the parent's lock. `fxPrepareForFork()` logs a warning per held name on POSIX; use `TFileMutex` or `TSemaphoreMutex` where holders fork. The cost: a holder killed without PHP shutdown keeps the permit (POSIX until `/dev/shm/Sync_Semaphore*` is removed or reboot; Windows while any process keeps the semaphore open). `TMutex` `AutoRelease` covers normal exit and fatal errors. `newSemaphore()` is the seam a test overrides. `init()` throws `syncmutex_extension_required` without the extension.

## Properties

| Property | Default | Meaning |
|---|---|---|
| `GlobalNamespace` | false | put the Windows name in `Global\` |
