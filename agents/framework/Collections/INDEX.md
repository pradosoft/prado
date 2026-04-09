# Collections/INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Directories

[./](../INDEX.md) > [Collections](./INDEX.md)

## Purpose

Data structure implementations for the Prado framework. All collections extend [`TComponent`](../TComponent.md) for property/event support and implement PHP standard interfaces (`ArrayAccess`, `Countable`, `IteratorAggregate`).

## Classes

### Core List/Map

- **[`TList`](TList.md)** — Integer-indexed collection. Key extension points: `insertAt()` and `removeAt()` (override these in subclasses, always call `parent::`). Supports read-only mode (`setReadOnly(true)`).

- **[`TMap`](TMap.md)** — Key-value collection (string/int keys). Uses dynamic methods `dyAddItem()` and `dyRemoveItem()` as behavior hooks on add/remove.

- **[`TQueue`](TQueue.md)** — FIFO queue extending [`TList`](TList.md). `enqueue()` / `dequeue()`.

- **[`TStack`](TStack.md)** — LIFO stack. `push()` / `pop()`.

### Priority Collections

- **[`TPriorityList`](TPriorityList.md)** — [`TList`](TList.md) variant where every item has a numeric priority (default `10`; lower = higher priority). Items are flattened into an ordered array on access and cached in `$_fd`. **Invalidate the cache when modifying items.** Configurable decimal precision (default 8).

- **[`TPriorityMap`](TPriorityMap.md)** — [`TMap`](TMap.md) variant with priority ordering. Items are key-indexed but iterated in priority order.

- **[`TPriorityCollectionTrait`](TPriorityCollectionTrait.md)** — Shared logic for both priority collections: `sortPriorities()`, `flattenPriorities()`, cache management. Implementing class must provide `getPriorityCombineStyle()` (`true` = merge duplicate priorities, `false` = replace).

**Interfaces:** [`IPriorityCollection`](IPriorityCollection.md), [`IPriorityItem`](IPriorityItem.md), [`IPriorityCapture`](IPriorityCapture.md), [`IPriorityProperty`](IPriorityProperty.md)

### Weak Reference Collections

- **[`TWeakList`](TWeakList.md)** — [`TList`](TList.md) backed by weak references; dead entries are automatically removed.

- **[`IWeakCollection`](IWeakCollection.md)**, **[`IWeakRetainable`](IWeakRetainable.md)** — Contracts for weak-reference collections.

- **[`ICollectionFilter`](ICollectionFilter.md)** — Converts items on input/output (used to wrap/unwrap `WeakReference` objects).

### Specialized Collections

- **[`TAttributeCollection`](TAttributeCollection.md)** — HTML/XML attribute storage (name=value pairs). Case-insensitive key lookup.

- **[`TListItemCollection`](TListItemCollection.md)** — Collection of `TListItem` objects for UI list controls ([`TDropDownList`](../Web/UI/WebControls/TDropDownList.md), [`TListBox`](../Web/UI/WebControls/TListBox.md), etc.).

- **[`TPagedList`](TPagedList.md)** — Paged collection with lazy loading via `OnFetchData` event. Properties: `PageSize`, `CurrentPageIndex`, `ItemCount`.

- **[`TDummyDataSource`](TDummyDataSource.md)** — Null/empty data source useful for testing.

- **[`TArraySubscription`](TArraySubscription.md)** / **[`TCollectionSubscription`](TCollectionSubscription.md)** — Subscribe a callback to collection change events.

- **[`TNull`](TNull.md)** — Null object pattern for collections (~11KB); provides a typed "empty" placeholder.

## Patterns & Gotchas

- **Read-only enforcement** — Call `$this->checkReadOnly()` at the top of any mutating method.
- **Priority cache** — `$_fd` in priority collections is a flattened array cache. Clear it (`$this->_fd = null`) whenever items change.
- **Priority string keys** — Priorities are stored as string-keyed arrays with configurable decimal precision; never assume integer keys.
- **`insertAt()` / `removeAt()` as extension points** — Subclasses should override these rather than overriding `add()` / `remove()`.
- **Serialization** — All collections support `__sleep()`/`__wakeup()` via `_getZappableSleepProps()`.
