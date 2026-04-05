# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Purpose

Data structure implementations for the Prado framework. All collections extend `TComponent` for property/event support and implement PHP standard interfaces (`ArrayAccess`, `Countable`, `IteratorAggregate`).

## Classes

### Core List/Map

- **`TList`** — Integer-indexed collection. Key extension points: `insertAt()` and `removeAt()` (override these in subclasses, always call `parent::`). Supports read-only mode (`setReadOnly(true)`).

- **`TMap`** — Key-value collection (string/int keys). Uses dynamic methods `dyAddItem()` and `dyRemoveItem()` as behavior hooks on add/remove.

- **`TQueue`** — FIFO queue extending TList. `enqueue()` / `dequeue()`.

- **`TStack`** — LIFO stack. `push()` / `pop()`.

### Priority Collections

- **`TPriorityList`** — TList variant where every item has a numeric priority (default `10`; lower = higher priority). Items are flattened into an ordered array on access and cached in `$_fd`. **Invalidate the cache when modifying items.** Configurable decimal precision (default 8).

- **`TPriorityMap`** — TMap variant with priority ordering. Items are key-indexed but iterated in priority order.

- **`TPriorityCollectionTrait`** — Shared logic for both priority collections: `sortPriorities()`, `flattenPriorities()`, cache management. Implementing class must provide `getPriorityCombineStyle()` (`true` = merge duplicate priorities, `false` = replace).

**Interfaces:** `IPriorityCollection`, `IPriorityItem`, `IPriorityCapture`, `IPriorityProperty`

### Weak Reference Collections

- **`TWeakList`** — TList backed by weak references; dead entries are automatically removed.

- **`IWeakCollection`**, **`IWeakRetainable`** — Contracts for weak-reference collections.

- **`ICollectionFilter`** — Converts items on input/output (used to wrap/unwrap `WeakReference` objects).

### Specialized Collections

- **`TAttributeCollection`** — HTML/XML attribute storage (name=value pairs). Case-insensitive key lookup.

- **`TListItemCollection`** — Collection of `TListItem` objects for UI list controls (TDropDownList, TListBox, etc.).

- **`TPagedList`** — Paged collection with lazy loading via `OnFetchData` event. Properties: `PageSize`, `CurrentPageIndex`, `ItemCount`.

- **`TDummyDataSource`** — Null/empty data source useful for testing.

- **`TArraySubscription`** / **`TCollectionSubscription`** — Subscribe a callback to collection change events.

- **`TNull`** — Null object pattern for collections (~11KB); provides a typed "empty" placeholder.

## Patterns & Gotchas

- **Read-only enforcement** — Call `$this->checkReadOnly()` at the top of any mutating method.
- **Priority cache** — `$_fd` in priority collections is a flattened array cache. Clear it (`$this->_fd = null`) whenever items change.
- **Priority string keys** — Priorities are stored as string-keyed arrays with configurable decimal precision; never assume integer keys.
- **`insertAt()` / `removeAt()` as extension points** — Subclasses should override these rather than overriding `add()` / `remove()`.
- **Serialization** — All collections support `__sleep()`/`__wakeup()` via `_getZappableSleepProps()`.
