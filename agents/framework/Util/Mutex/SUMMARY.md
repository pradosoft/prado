# Util/Mutex/SUMMARY.md

Named lock modules over files, semaphores, process memory, caches, Redis, and databases.

## Classes

- **`IMutex`** — `acquire()`, `release()`, `isAcquired()` of a lock name.

- **`TMutex`** — Abstract module base: held names, `RetryDelay` retries through `TAsync::sleep()`, `synchronize()`, `AutoRelease`, `KeyPrefix`, fork safety.

- **`TFileMutex`** — `flock()` on a lock file in `MutexPath`.

- **`TSemaphoreMutex`** — System V semaphore through `sysvsem`.

- **`TMemoryMutex`** — In-process lock table for fibers and tests.

- **`TCacheMutex`** — Token lease through `ICache::add()`.

- **`TRedisMutex`** — `SET NX PX` lease with an atomic Lua release.

- **`TBaseDbMutex`** — Abstract base of the database mutexes (`ConnectionID`, `DbConnection`, static driver check).

- **`TDbMutex`** — Universal database mutex; routes to the driver's server lock or the lock table, with static `getServerMutexClass()` and `getIsServerLockAvailable()` for a connection or DSN.

- **`TMysqlMutex`**, **`TPgsqlMutex`**, **`TOracleMutex`**, **`TMssqlMutex`** — Server session locks.

- **`TDbTableMutex`** — Lock-table lease on any PDO driver; SQLite in the runtime path by default.

- **`TMutexException`** — Thrown by `synchronize()` when the lock is not acquired.
