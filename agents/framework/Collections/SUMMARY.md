# SUMMARY.md

Data structure implementations extending `TComponent` with PHP standard interfaces (`ArrayAccess`, `Countable`, `IteratorAggregate`).

## Classes

- **`TList`** — Integer-indexed collection; key extension points: `insertAt()` and `removeAt()`; supports read-only mode.

- **`TMap`** — Key-value collection using dynamic methods `dyAddItem()` and `dyRemoveItem()` as behavior hooks.

- **`TQueue`** — FIFO queue extending TList; methods: `enqueue()` / `dequeue()`.

- **`TStack`** — LIFO stack; methods: `push()` / `pop()`.

- **`TPriorityList`** — TList variant where every item has a numeric priority; items flattened into ordered array on access.

- **`TPriorityMap`** — TMap variant with priority ordering; key-indexed but iterated in priority order.

- **`TPriorityCollectionTrait`** — Shared logic for both priority collections: `sortPriorities()`, `flattenPriorities()`, cache management.

- **`TWeakList`** — TList backed by weak references; dead entries automatically removed.

- **`TAttributeCollection`** — HTML/XML attribute storage (name=value pairs); case-insensitive key lookup.

- **`TListItemCollection`** — Collection of `TListItem` objects for UI list controls.

- **`TPagedList`** — Paged collection with lazy loading via `OnFetchData` event; properties: `PageSize`, `CurrentPageIndex`, `ItemCount`.

- **`TDummyDataSource`** — Null/empty data source useful for testing.

- **`TArraySubscription`** / **`TCollectionSubscription`** — Subscribe a callback to collection change events.

- **`TNull`** — Null object pattern for collections; provides a typed "empty" placeholder.

- **`ICollectionFilter`** — Converts items on input/output for wrapping/unwraping `WeakReference` objects.
