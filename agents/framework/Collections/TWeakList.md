# Collections/TWeakList

### Directories
[framework](../INDEX.md) / [Collections](./INDEX.md) / **`TWeakList`**

## Class Info
**Location:** `framework/Collections/TWeakList.php`
**Namespace:** `Prado\Collections`
**Since:** 4.3.0

## Overview

TWeakList is an integer-indexed collection that stores object values as `WeakReference` so the list does not prevent garbage collection. Non-objects, scalars, `Closure`, and `IWeakRetainable` objects are stored directly. Arrays are traversed recursively and any nested objects are also weakened.

## Inheritance & Interfaces

Extends [TList](./TList.md). Implements [IWeakCollection](./IWeakCollection.md) and [ICollectionFilter](./ICollectionFilter.md). Uses [TWeakCollectionTrait](./TWeakCollectionTrait.md).

## Storage Rules

| Item type | Stored as |
|---|---|
| Scalar / null | Directly |
| `Closure` | Directly (strongly retained) |
| `IWeakRetainable` / `TEventHandler` | Directly; inner callable object tracked in WeakMap |
| Any other object | `WeakReference::create($object)` |
| Explicit `WeakReference` | As given; its referent is tracked in WeakMap |
| Array | Recursively weakened (nested objects become WeakReferences) |
| `Traversable` + `ArrayAccess` container (e.g. `ArrayObject`) | A weakened copy (`clone`); the caller's container is unchanged |

## DiscardInvalid Mode

Controlled by `DiscardInvalid` (default = opposite of `ReadOnly`):

- **`true`** (default for mutable lists): dead WeakReferences are removed on detection, shrinking the list.
- **`false`** (default for read-only lists): dead entries remain; GC'd objects are returned as `null`.

Once set externally, `DiscardInvalid` is locked — only the object itself may change it again.

## Constructor

```php
new TWeakList(
    array|\Iterator|null $data = null,
    ?bool $readOnly        = null,
    ?bool $discardInvalid  = null   // null = opposite of $readOnly
)
```

## ICollectionFilter Methods

```php
public static function filterItemForInput(mixed &$item): void
// Recursively wraps objects (except Closure, IWeakRetainable) in WeakReference.
// Works on plain items, arrays, and Traversable+ArrayAccess containers.
// A container is weakened as a clone; $item receives the clone.

public static function filterItemForOutput(mixed &$item): void
// Recursively resolves WeakReferences back to the original object (null if GC'd).
// A TEventHandler with no live inner handler resolves to null.
// A container is resolved into a clone, so the stored container keeps its WeakReferences.
```

## Key TList Overrides

```php
public function getDiscardInvalid(): bool
public function setDiscardInvalid(?bool $value): void   // locked after first external set

// Called automatically before reads/writes when DiscardInvalid is true and
// the WeakMap reports a change (weakChanged()):
protected function scrubWeakReferences(): void
```

`scrubWeakReferences()` iterates `$_d` in reverse, removes entries whose WeakReference is dead, and calls `weakResetCount()`. A re-entrancy guard (`isScrubbing()`) prevents PHP's cyclic GC from triggering a nested scrub mid-loop.

## WeakMap Bookkeeping

`weakCustomAdd(object $object)` and `weakCustomRemove(object $object)` proxy to the trait's `weakAdd()`/`weakRemove()`. For `TEventHandler` items the inner handler object (not the wrapper) is tracked; `$_eventHandlerCount` is incremented/decremented for every `TEventHandler`.

| Item | Bookkeeping |
|---|---|
| `TEventHandler` with a live handler object | Counted; handler object tracked |
| `TEventHandler` without a handler object (e.g. `'strlen'`) | Counted; not tracked; kept when `DiscardInvalid` turns on |
| `TEventHandler` whose handler object is collected before it is added | Counted; `weakMarkChanged()` so the next scrub removes it |
| `Traversable` + `ArrayAccess` container | Not tracked (stored strongly, like an array) |
| Explicit `WeakReference` with a live referent | Referent tracked, whichever type it is; a referent `TEventHandler` is not counted, because the stored entry is the `WeakReference` |
| Explicit `WeakReference` with a collected referent | `weakMarkChanged()` so the next scrub removes it |

`remove()`, `removeAt()`, and `offsetSet()` pass the removed stored entry to `weakCustomRemove()`, which resolves a stored `WeakReference` to the same referent `weakCustomAdd()` tracked.

`internalInsertAt()` is the insert path of `add()`, `insertAt()`, `insertBefore()`, `insertAfter()`, `copyFrom()`, `mergeWith()`, and `offsetSet()`. It tracks the item after `TList::insertAt()` succeeds, so a read-only list or an invalid index leaves the WeakMap and `$_eventHandlerCount` unchanged.

## clear()

`clear()` removes from the end while `$_c > 0`, then resets `$_eventHandlerCount` and the WeakMap. See [TWeakCollectionTrait](./TWeakCollectionTrait.md) "Clear Contract". Invalid entries are scrubbed first, so a read-only list holding only collected objects clears without error; a read-only list with a live entry throws `list_readonly`.

## TEventHandler Search Semantics

- Searching for a `TEventHandler` instance finds only that exact object.
- Searching for a plain callable finds direct callables **and** any `TEventHandler` wrapping that callable (two-pass search when `$_eventHandlerCount > 0`).

## Serialization

WeakReferences cannot be serialized. On `__sleep()` the data array is excluded. On `__wakeup()`, the WeakMap is re-initialized (if `DiscardInvalid` is true) from an empty state. `__clone()` clones the WeakMap via `weakClone()`.

## Patterns & Gotchas

- **Container items** — A `Traversable` + `ArrayAccess` item is stored as a weakened copy and read as a resolved copy. A search by the original container does not match the stored copy by identity.
- **Array items** — Objects nested inside array items are also weakened. If a nested object is GC'd, `filterItemForOutput` returns `null` for that nested position; the enclosing array item itself remains in the list.
- **Closures survive GC** — A `Closure` stored in TWeakList is strongly retained; the list is the owning reference.
- **Count/index stability** — In `DiscardInvalid = true` mode, positions shift when entries are purged. Do not cache indices across potential GC points.

## See Also

- [TList](./TList.md) — Base list class
- [IWeakCollection](./IWeakCollection.md) — Marker interface for weak collections
- [IWeakRetainable](./IWeakRetainable.md) — Objects stored directly (not weakened)
- [ICollectionFilter](./ICollectionFilter.md) — Input/output item conversion contract
- [TWeakCollectionTrait](./TWeakCollectionTrait.md) — WeakMap bookkeeping
- [TWeakCallableCollection](./TWeakCallableCollection.md) — Priority-list variant for callables
- [TWeakMap](./TWeakMap.md) — Map variant with weak values
