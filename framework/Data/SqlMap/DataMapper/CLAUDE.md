# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Purpose

Runtime execution engine for SqlMap: type handling, caching, lazy loading, object property access, and exception types.

## Classes

### Type Handling

- **`TSqlMapTypeHandler`** — Abstract base for custom type handlers. Implement `getParameter($object)` (PHP → SQL) and `getResult($type, $value)` (SQL → PHP). Register with `TSqlMapTypeHandlerRegistry`.

- **`TSqlMapTypeHandlerRegistry`** — Registry mapping JDBC type names and PHP class names to `TSqlMapTypeHandler` instances. Looked up during parameter binding and result mapping.

### Caching

- **`TSqlMapCache`** — Abstract base for statement-level result caches. Interface: `get($key)`, `put($key, $value)`, `flush()`.

- **`TSqlMapLruCache`** — LRU (Least Recently Used) eviction cache. Configurable `$size` (default 100 entries).

- **`TSqlMapFifoCache`** — FIFO (First In, First Out) eviction cache. Fixed-size circular buffer.

- **`TSqlMapApplicationCache`** — Delegates to the Prado application cache (`ICache`). Wraps `TCache::get()`/`set()`/`delete()`.

### Lazy Loading

- **`TLazyLoadList`** — Proxy list that defers loading of a nested collection until first access. Holds the statement ID, parameters, and target object/property. Implements `ArrayAccess` and `Countable`.

- **`TObjectProxy`** — Generic proxy that intercepts property access on a result object to trigger lazy loads of nested selects.

### Object/Property Utilities

- **`TPropertyAccess`** — Static utility for reading/writing object and array properties by name. Handles `getXxx()`/`setXxx()` accessors, public fields, and array keys uniformly.

### Exceptions

- **`TSqlMapException`** — Base SqlMap exception.
- **`TSqlMapConfigurationException`** — Configuration/parsing errors.
- **`TSqlMapExecutionException`** — Runtime SQL execution errors.
- **`TSqlMapDuplicateException`** — Duplicate statement/map ID during configuration.
- **`TSqlMapUndefinedException`** — Reference to unknown statement or map.
- **`TInvalidPropertyException`** — Property access failure (via `TPropertyAccess`).

### Paged Results

- **`TSqlMapPagedList`** — Paged result list for SqlMap queries. Properties: `PageSize`, `PageIndex`, `PageCount`. Wraps a `TSqlMapGateway` call and fetches pages on demand.

## Patterns & Gotchas

- **Lazy loading proxy** — `TLazyLoadList` and `TObjectProxy` are transparent proxies; they are returned in place of the actual collection/object until first access.
- **Cache key collision** — `TSqlMapCacheKey` hashes statement ID + serialised parameter; ensure parameters are serialisable.
- **Type handler registration order** — More-specific handlers (class name) take priority over JDBC-type handlers.
- **`TPropertyAccess` reflection** — Uses PHP reflection for getter/setter lookup; prefer public properties in high-frequency result objects for performance.
