# Util/Mutex/TFileMutex

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Mutex](./INDEX.md) / **`TFileMutex`**

## Class Info
**Location:** `framework/Util/Mutex/TFileMutex.php`
**Namespace:** `Prado\Util\Mutex`
**Extends:** [`TMutex`](TMutex.md)
**Since:** 4.4.0

## Overview
Locks with `flock(LOCK_EX | LOCK_NB)` on `MutexPath/md5(KeyPrefix . name).lock`, opened with mode `c`. After locking, `isSameFile()` compares the handle's inode with the path's, so a handle opened before another holder deleted the file fails and retries. Release unlinks the file and then unlocks (on Windows it unlocks, closes, then unlinks). `forgetLock()` closes the child's descriptor without unlocking. A missing directory is created with `DirMode`; failure throws `filemutex_mutexpath_invalid`.

## Properties

| Property | Default | Meaning |
|---|---|---|
| `MutexPath` | runtime `mutex` | directory of the lock files; a path or a namespace |
| `FileMode` | null | mode of a lock file; octal string or int |
| `DirMode` | 0775 | mode of a created directory |
