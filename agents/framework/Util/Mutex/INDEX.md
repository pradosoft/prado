# Util/Mutex

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / **`Mutex`**

## Purpose

Named locks (`Prado\Util\Mutex`). A mutex module excludes other holders of a lock name: processes on a host, clients of a cache or Redis server, or connections to a database. The set covers the Yii2 mutexes (`FileMutex`, `MysqlMutex`, `PgsqlMutex`, `OracleMutex`, yii2-redis `Mutex`) and adds a System V semaphore, an in-process, a cache, a SQL Server, a portable lock-table, and a universal database mutex that routes by driver.

## Contract and Base

- **[`IMutex`](IMutex.md)** — `acquire(string $name, float|int $timeout = 0): bool`, `release(string $name): bool`, `isAcquired(string $name): bool`.
- **[`TMutex`](TMutex.md)** — Abstract `TModule` base. Keeps the held names, retries `acquireLock()` every `RetryDelay` ms (default 50) through `TAsync::sleep()` until the timeout, and runs `synchronize($name, $callback, $timeout)`. `AutoRelease` (default true) releases on destruction and at shutdown; a throwing release there is logged. `KeyPrefix` prepends to every name. On `fxRestoreAfterFork` a child forgets the parent's names through `forgetLock()` without releasing them.
- **[`TMutexException`](TMutexException.md)** — `TSystemException` from `synchronize()` when the lock is not acquired (`mutex_acquire_failed`).
- **[`TBaseDbMutex`](TBaseDbMutex.md)** — Abstract base of the database mutexes. `TDbPropertiesTrait` (`ConnectionID`), `setDbConnection()`, static `getSupportedDrivers()` and `isDriverSupported()` checked on first use (`dbmutex_driver_unsupported`), and `getLimitedLockKey()` that hashes a name past the server limit with SHA-1. Its destructor leaves the shared connection active.

## Backends

| Class | Backend | Excludes | Lease |
|---|---|---|---|
| [`TFileMutex`](TFileMutex.md) | `flock()` on `MutexPath/md5(name).lock` | processes of a host | no |
| [`TSemaphoreMutex`](TSemaphoreMutex.md) | System V semaphore (`sysvsem`) | processes of a host | no |
| [`TMemoryMutex`](TMemoryMutex.md) | static table of the process | mutexes and fibers of a process | no |
| [`TCacheMutex`](TCacheMutex.md) | `ICache::add()` of a token | clients of the cache | `Expire` (30 s) |
| [`TRedisMutex`](TRedisMutex.md) | `SET NX PX`, Lua release | clients of the Redis server | `Expire` (30 s) |
| [`TDbMutex`](TDbMutex.md) | routes to the driver's server lock, else the lock table | per routed mutex | per routed mutex |
| [`TMysqlMutex`](TMysqlMutex.md) | `GET_LOCK()` / `RELEASE_LOCK()` | sessions of the server | no |
| [`TPgsqlMutex`](TPgsqlMutex.md) | `pg_try_advisory_lock(int4, int4)` | sessions of the server | no |
| [`TOracleMutex`](TOracleMutex.md) | `DBMS_LOCK.REQUEST` / `RELEASE` | sessions of the server | no |
| [`TMssqlMutex`](TMssqlMutex.md) | `sp_getapplock` / `sp_releaseapplock` | sessions of the server | no |
| [`TDbTableMutex`](TDbTableMutex.md) | primary-key row in `pradomutex` | connections to the database | `Expire` (30 s) |

## Patterns & Gotchas

- **Universal or per driver.** Configure [`TDbMutex`](TDbMutex.md) to let the connection's driver choose; configure a driver class to pin the backend or set its own properties, such as `TOracleMutex.LockMode`. `TDbMutex::getIsServerLockAvailable($dsn)` reports the choice before configuration.

- **One attempt, base retries.** Each backend makes a non-blocking attempt (`GET_LOCK(name, 0)`, `@LockTimeout = 0`, `LOCK_NB`). Waiting happens in `TMutex::acquire()`, so a fiber yields during the wait and every backend honors a fractional timeout.
- **Not re-entrant.** Acquiring a name the mutex holds waits like a contended attempt, so fibers that share one mutex take turns.
- **Session locks.** `TMysqlMutex`, `TPgsqlMutex`, `TOracleMutex`, and `TMssqlMutex` locks belong to the connection's session. The server drops them when the connection closes. Mutexes that share one connection do not exclude each other.
- **Leases.** `TCacheMutex`, `TRedisMutex`, and `TDbTableMutex` locks expire after `Expire` seconds, so a crashed holder frees them, and a holder whose work outlasts the lease loses it. A release checks the token; `TCacheMutex` checks and deletes in two calls, `TRedisMutex` in one script.
- **Transactions.** `DBMS_LOCK.ALLOCATE_UNIQUE` commits; a failed `TDbTableMutex` insert aborts a PostgreSQL transaction. Give those mutexes a connection of their own.
- **Fork.** A child forgets the inherited names. `TFileMutex` closes its copy of the descriptor (the parent's lock remains); `TSemaphoreMutex` is created without PHP auto-release so the child's teardown does not post the parent's permit.
- **Optimistic locking** (Yii2 `ActiveRecord::optimisticLock()`) belongs to the record layer in `pradosoft/prado-activerecord`.

## Conventions

- Registered in `framework/classes.php`; `@since 4.4.0`.
- Error keys: `mutex_acquire_failed`, `mutex_retrydelay_invalid`, `mutex_expire_invalid`, `filemutex_mutexpath_invalid`, `semaphoremutex_extension_required`, `redismutex_extension_required`, `dbmutex_driver_unsupported`.
- Tests: `tests/unit/Util/Mutex/`; `Harness/Util/Mutex/TTestLockConnection` and `TTestLockCommand` script database results without a server.
