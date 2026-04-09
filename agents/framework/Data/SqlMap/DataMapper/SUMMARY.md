# SUMMARY.md

Runtime execution engine for SqlMap: type handling, caching, lazy loading, object property access, and exception types.

## Classes

- **`TSqlMapTypeHandler`** — Abstract base for custom type handlers; implement `getParameter($object)` (PHP → SQL) and `getResult($type, $value)` (SQL → PHP).

- **`TSqlMapTypeHandlerRegistry`** — Registry mapping JDBC type names and PHP class names to `TSqlMapTypeHandler` instances.

- **`TSqlMapCache`** — Abstract base for statement-level result caches; interface: `get($key)`, `put($key, $value)`, `flush()`.

- **`TSqlMapLruCache`** — LRU eviction cache; configurable `$size` (default 100 entries).

- **`TSqlMapFifoCache`** — FIFO eviction cache; fixed-size circular buffer.

- **`TSqlMapApplicationCache`** — Delegates to Prado application cache (`ICache`).

- **`TLazyLoadList`** — Proxy list deferring loading of nested collection until first access; implements `ArrayAccess` and `Countable`.

- **`TObjectProxy`** — Generic proxy intercepting property access on result object to trigger lazy loads.

- **`TPropertyAccess`** — Static utility for reading/writing object and array properties by name; handles `getXxx()`/`setXxx()` accessors, public fields, array keys.

- **`TSqlMapException`** / **`TSqlMapConfigurationException`** / **`TSqlMapExecutionException`** / **`TSqlMapDuplicateException`** / **`TSqlMapUndefinedException`** — SqlMap exception types.

- **`TInvalidPropertyException`** — Property access failure via `TPropertyAccess`.

- **`TSqlMapPagedList`** — Paged result list; properties: `PageSize`, `PageIndex`, `PageCount`.
