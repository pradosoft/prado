# Collections/TWeakCallableCollection

### Directories
[framework](../INDEX.md) / [Collections](./INDEX.md) / **`TWeakCallableCollection`**

## Class Info
**Location:** `framework/Collections/TWeakCallableCollection.php`
**Namespace:** `Prado\Collections`

## Overview
`TWeakCallableCollection` is a priority-ordered list of callables. It extends [TPriorityList](./TPriorityList.md) and is the backing store for Prado's event handler lists (both component `on*` events and global `fx*` events).

Its primary purpose is to prevent circular references: object-based callables (e.g., `[$object, 'method']`) are stored as `[WeakReference::create($object), 'method']` internally. On output all weak references are resolved back to their real objects. If the referenced object has been garbage-collected, the entry is treated as invalid and optionally removed (scrubbed).

`Closure` and objects implementing [IWeakRetainable](./IWeakRetainable.md) are stored directly (not weakened) because they may be the only reference to the handler in the application.

## Interfaces Implemented

- Extends [TPriorityList](./TPriorityList.md) (which implements `IteratorAggregate`, `ArrayAccess`, `Countable`, [IPriorityCollection](./IPriorityCollection.md))
- Implements [IWeakCollection](./IWeakCollection.md) — marks the collection as one that uses WeakReference internally
- Implements [ICollectionFilter](./ICollectionFilter.md) — declares the static `filterItemForInput` / `filterItemForOutput` contract

Uses [TWeakCollectionTrait](./TWeakCollectionTrait.md) for the PHP `WeakMap`-based change-detection bookkeeping.

## Key Properties / Constants

| Property | Type | Default | Description |
|---|---|---|---|
| `DiscardInvalid` | `bool` | opposite of `ReadOnly` | When `true`, dead WeakReferences are automatically purged before every read/write operation. Mutable lists default to `true`; read-only lists default to `false`. Can only be set once (via `Prado::isCallingSelf()` guard). |
| `AutoGlobalListen` | `bool` | always `false` | Hard-coded to `false` to prevent catastrophic recursion when global events fire. |

Internal counters:
- `$_eventHandlerCount` — tracks how many `TEventHandler` items are in the list; used to skip the secondary TEventHandler search in `indexOf()` when count is zero.

## Key Methods

### Static Filter Methods (ICollectionFilter)

```php
public static function filterItemForInput(mixed &$handler, bool $validate = false): void
```
Converts an object-based callable to WeakReference form for storage. Skips `Closure` and `IWeakRetainable`. If `$validate` is true and the input is not callable, throws `TInvalidDataValueException`.

```php
public static function filterItemForOutput(mixed &$handler): void
```
Reverses the WeakReference wrapper for a single item. If the WeakReference target has been GC'd, sets `$handler` to `null`. Used by all read paths.

### Insertion

```php
public function insertAt(int $index, mixed $item): float
public function insertAtIndexInPriority(mixed $item, int|null|false $index = null, numeric $priority = null, bool $preserveCache = false)
protected function internalInsertAtIndexInPriority(mixed $items, ...): mixed
```

`internalInsertAtIndexInPriority` is the core insertion path. It:
1. Calls `collapseDiscardInvalid()` to finalize the discard setting.
2. Supports inserting an array of items in bulk.
3. Honors [IPriorityItem](./IPriorityItem.md) and [IPriorityCapture](./IPriorityCapture.md) interfaces.
4. Calls `weakCustomAdd()` to register the object in the WeakMap.
5. Calls `filterItemForInput()` to weaken the callable before passing to `parent::`.

### Removal

```php
public function remove(mixed $item, null|bool|float $priority = false): int
public function removeAt(int $index): mixed
public function removeAtIndexInPriority(int $index, numeric $priority = null): mixed
protected function internalRemoveAtIndexInPriority(int $index, numeric $priority = null): mixed
public function clear(): void
```

All public removal methods call `scrubWeakReferences()` first. The internal `internalRemoveAtIndexInPriority` resolves the stored item back to a real callable via `filterItemForOutput` before returning, and calls `weakCustomRemove()`.

### Search

```php
public function indexOf(mixed $item, mixed $priority = false): int
public function priorityOf(mixed $item, bool $withindex = false): array|false|numeric
public function contains(mixed $item): bool
```

`indexOf` has a two-pass search strategy:
1. First tries `array_search` against the (weakened) flat cache.
2. If `$_eventHandlerCount > 0`, does a second pass calling [TEventHandler](../TEventHandler.md)::isSameHandler() to find handlers wrapped in `TEventHandler` objects.

Searching for a `TEventHandler` instance finds only that exact object. Searching for a plain callable finds both direct callables and any `TEventHandler` wrapping that callable.

### Read Accessors

```php
public function getIterator(): \Iterator       // scrubs, flattens, filters output
public function getCount(): int                // scrubs before counting
public function itemAt(int $index): mixed      // scrubs, flattens, filters output
public function itemsAtPriority(numeric $priority = null): ?array  // scrubs, filters output
public function itemAtIndexInPriority(int $index, numeric $priority = null): mixed  // scrubs, filters output
public function getPriorities(): array         // scrubs before returning
public function getPriorityCount(numeric $priority = null): int    // scrubs before counting
```

### Weak Reference Management

```php
protected function scrubWeakReferences(): void
```
If `DiscardInvalid` is true and the WeakMap reports a change (`weakChanged()`), iterates `$_d` in reverse order per priority, removes any entry whose WeakReference is `null`, decrements `$_c`, clears `$_fd`, and calls `weakResetCount()`.

```php
protected function weakCustomAdd(object $object): void
protected function weakCustomRemove(object $object): void
```
Proxy to `weakAdd()`/`weakRemove()` from [TWeakCollectionTrait](./TWeakCollectionTrait.md). For `TEventHandler` items, registers/deregisters the handler's *inner object* (not the `TEventHandler` shell) and adjusts `$_eventHandlerCount`.

### DiscardInvalid Lifecycle

```php
public function getDiscardInvalid(): bool
public function setDiscardInvalid(null|bool|string $value): void
protected function collapseDiscardInvalid(): void
```

Setting `DiscardInvalid` to `true` on an already-populated list immediately:
- Starts the WeakMap tracker (`weakStart()`).
- Iterates existing items and registers all living objects; dead WeakReferences are removed inline.

Setting it to `false` calls `weakStop()`.

## Constructor

```php
public function __construct(
    $data = null,
    $readOnly = null,
    $defaultPriority = null,
    $precision = null,
    $discardInvalid = null
)
```

`$discardInvalid`:
- `null` (default) — resolved lazily to `!ReadOnly` when first needed.
- `false` — disables scrubbing even on mutable lists.
- `true` — enables scrubbing even on read-only lists.

Construction order matters: if `$discardInvalid = false` or (`null` + `$readOnly = true`), scrubbing is disabled before data is loaded to avoid scanning during construction.

## Serialization

`__wakeup()` re-initializes the WeakMap tracker if `DiscardInvalid` is true. Because WeakReferences do not serialize, the list effectively wakes up empty (no items survive serialization).

`__clone()` calls `weakClone()` to clone the internal WeakMap.

## Patterns & Gotchas

- **Do not store non-callables** — The list validates callables on insert when `$validate = true` in `filterItemForInput`. Items must be PHP callables (string, array, Closure, or invokable object).
- **TEventHandler dual-search** — When removing or searching for a handler, passing a plain callable finds it even if it is wrapped in a `TEventHandler`. This is intentional: event dispatch attaches handlers in `TEventHandler` wrappers but removal by callable must still work.
- **`getAutoGlobalListen()` is hard-coded false** — Do not override this. Allowing TWeakCallableCollection to listen to global events would cause infinite recursion during `fx` event dispatch.
- **Inherited `$_d` structure** — Internal storage is `array<priority_string, callable[]>` where each callable may be a WeakReference-wrapped form. Never read `$_d` directly without filtering output; always go through the public API.
- **`$_fd` flat cache** — Inherited from [TPriorityList](./TPriorityList.md). `scrubWeakReferences()` sets it to `null` after purging dead entries. `flattenPriorities()` regenerates it. The flat cache contains the weakened (un-resolved) callables; `filterItemsForOutput` is always applied before exposing them.
- **Read-only + DiscardInvalid** — A read-only list can still have dead WeakReferences purged if `DiscardInvalid` is explicitly set to `true`. This is useful for lists that are fixed in content but still need to exclude GC'd handlers.
- **Precision** — Priority precision is inherited from [TPriorityList](./TPriorityList.md) (default 8 decimal places). Priorities are stored as string-keyed array keys.
