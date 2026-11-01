# Util/Mutex/TMysqlMutex

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Mutex](./INDEX.md) / **`TMysqlMutex`**

## Class Info
**Location:** `framework/Util/Mutex/TMysqlMutex.php`
**Namespace:** `Prado\Util\Mutex`
**Extends:** [`TDbMutex`](TDbMutex.md)
**Since:** 4.4.0

## Overview
`SELECT GET_LOCK(:name, 0)` and `SELECT RELEASE_LOCK(:name)`; 1 is success. Names over 64 characters become SHA-1. Driver `mysql`.
