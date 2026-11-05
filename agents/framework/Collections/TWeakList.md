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
| Array | Recursively weakened (nested objects become WeakReferences) |

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

public static function filterItemForOutput(mixed &$item): void
// Recursively resolves WeakReferences back to the original object (null if GC'd).
// A TEventHandler with no live inner handler resolves to null.
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

`weakCustomAdd(object $object)` and `weakCustomRemove(object $object)` proxy to the trait's `weakAdd()`/`weakRemove()`. For `TEventHandler` items the inner handler object (not the wrapper) is tracked; `$_eventHandlerCount` is incremented/decremented accordingly.

## TEventHandler Search Semantics

- Searching for a `TEventHandler` instance finds only that exact object.
- Searching for a plain callable finds direct callables **and** any `TEventHandler` wrapping that callable (two-pass search when `$_eventHandlerCount > 0`).

## Serialization

WeakReferences cannot be serialized. On `__sleep()` the data array is excluded. On `__wakeup()`, the WeakMap is re-initialized (if `DiscardInvalid` is true) from an empty state. `__clone()` clones the WeakMap via `weakClone()`.

## Patterns & Gotchas

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
