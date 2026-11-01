# Util/Mutex/TOracleMutex

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Mutex](./INDEX.md) / **`TOracleMutex`**

## Class Info
**Location:** `framework/Util/Mutex/TOracleMutex.php`
**Namespace:** `Prado\Util\Mutex`
**Extends:** [`TBaseDbMutex`](TBaseDbMutex.md)
**Since:** 4.4.0

## Overview
A PL/SQL block runs `DBMS_LOCK.ALLOCATE_UNIQUE(:name, handle)` and `:status := DBMS_LOCK.REQUEST(handle, DBMS_LOCK.<LockMode>, 0, <ReleaseOnCommit>)`; release runs `DBMS_LOCK.RELEASE(handle)`. Status 0 is success; `:status` is an input/output parameter. `ALLOCATE_UNIQUE` commits the session's transaction. Names over 128 characters become SHA-1. Driver `oci`; the user needs `EXECUTE` on `DBMS_LOCK`.

## Properties

| Property | Default | Meaning |
|---|---|---|
| `LockMode` | 'X_MODE' | one of `LOCK_MODES`: NL, SS, SX, S, SSX, X |
| `ReleaseOnCommit` | false | whether a commit releases the lock |
