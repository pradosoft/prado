# Util/Mutex/TMssqlMutex

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Mutex](./INDEX.md) / **`TMssqlMutex`**

## Class Info
**Location:** `framework/Util/Mutex/TMssqlMutex.php`
**Namespace:** `Prado\Util\Mutex`
**Extends:** [`TBaseDbMutex`](TBaseDbMutex.md)
**Since:** 4.4.0

## Overview
`SET NOCOUNT ON` batches run `sp_getapplock @Resource = :name, @LockMode = 'Exclusive', @LockOwner = 'Session', @LockTimeout = 0` (a return of 0 or 1 is a grant) and `sp_releaseapplock` (0 is a release), and `SELECT` the return code. Names over 255 characters become SHA-1. Drivers `sqlsrv`, `dblib`, `mssql`.
