# Util/Mutex/TMutex

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Mutex](./INDEX.md) / **`TMutex`**

## Class Info
**Location:** `framework/Util/Mutex/TMutex.php`
**Namespace:** `Prado\Util\Mutex`
**Extends:** `Prado\TModule`
**Implements:** [`IMutex`](IMutex.md)
**Since:** 4.4.0

## Overview
Base of the mutex modules. A subclass implements `acquireLock($name)` (one non-blocking attempt), `releaseLock($name)`, and optionally `forgetLock($name)` (drop local state in a forked child).

`acquire()` tries once, then sleeps `min(remaining, RetryDelay)` through `TAsync::sleep()` between attempts until the timeout. A held name is not re-entered: the attempt waits like any contended one. `release()` forgets the name even when the backend reports failure, and returns the backend result. `synchronize($name, $callback, $timeout)` acquires, runs `$callback($mutex)`, and releases in `finally`; it throws [`TMutexException`](TMutexException.md) when the lock is not acquired.

With `AutoRelease`, the destructor and a shutdown function (registered on the first acquire, holding the mutex through a `WeakReference`) release the held names; a release that throws there is logged as a warning. `fxRestoreAfterFork` with `pid` 0 calls `forgetLock()` for each held name and clears them.

## Properties

| Property | Default | Meaning |
|---|---|---|
| `AutoRelease` | true | release the held names on destruction and at shutdown |
| `RetryDelay` | 50 | milliseconds between attempts; at least 1 (`mutex_retrydelay_invalid`) |
| `KeyPrefix` | '' | prepended to every name by `getLockKey()` |
| `AcquiredLocks` | — | read-only list of held names |
