# Collections/TWeakMap

### Directories
[framework](../INDEX.md) / [Collections](./INDEX.md) / **`TWeakMap`**

## Class Info
**Location:** `framework/Collections/TWeakMap.php`
**Namespace:** `Prado\Collections`

## Overview

TWeakMap is a key-value collection (extending [TMap](./TMap.md)) where object *values* are held as `WeakReference`, so the map does not prevent its values from being garbage-collected. Keys are always strongly retained (PHP array keys must be strings or integers).

## Inheritance & Interfaces

Extends [TMap](./TMap.md). Implements [IWeakCollection](./IWeakCollection.md) and [ICollectionFilter](./ICollectionFilter.md). Uses [TWeakCollectionTrait](./TWeakCollectionTrait.md).

## Storage Rules

| Value type | Stored as |
|---|---|
| Non-object (null, string, int, bool, array) | Directly (no weakening) |
| `Closure` | Directly (strongly retained) |
| `IWeakRetainable` / `TEventHandler` | Directly; for `TEventHandler`, the *inner* callable object is tracked in the WeakMap |
| Any other object | Wrapped in `WeakReference::create($object)` |
| Explicit `WeakReference` | As given; its referent is tracked in the WeakMap, and a referent already collected calls `weakMarkChanged()` so the next scrub removes it |

## DiscardInvalid Mode

Controlled by `setDiscardInvalid(?bool $value)`. The mode governs what happens when a weakly-held value's object is garbage-collected:

- **`true`**: the entry is silently removed. Count and key set shrink automatically. Use when the map is a live index and dead entries should be invisible.
- **`false`**: the entry is retained with the value resolving to `null` on read. Count and key set remain stable. Use when callers need a stable key set (e.g., read-only snapshots or maps that must not silently shrink).
- **`null`** (default — deferred resolution): without initial data, the actual mode is not decided at construction time. The first operation that needs to know the mode (any add, remove, count, or iteration) resolves it automatically: mutable maps resolve to `true`; read-only maps resolve to `false`. After resolution the value is fixed for the lifetime of the object.

With initial data, the constructor resolves `null` to the opposite of the final `ReadOnly` after loading the data. The `null` default exists so that a map constructed before its `ReadOnly` state is finalised still ends up with a sensible mode. Pass `$discardInvalid` explicitly in the constructor if you know the desired mode up front — this avoids any ambiguity.

Turning `DiscardInvalid` on iterates a snapshot of the entries: live objects are registered, collected entries are removed, and a `TEventHandler` without a handler object is kept. A destructor that removes an entry during this pass does not break the bookkeeping (see [TWeakCollectionTrait](./TWeakCollectionTrait.md) `weakRemove()`).

**Lock:** once resolved from `null`, or set explicitly to `true`/`false`, `DiscardInvalid` is locked. Only code inside the class itself (constructor or protected subclass methods) may change it further. External callers attempting `setDiscardInvalid()` after resolution receive `TInvalidOperationException`.

## Constructor

```php
new TWeakMap(
    array|\Traversable|null $data = null,
    ?bool $readOnly = null,
    ?bool $discardInvalid = null
)
```

## Key Methods

```php
// Standard TMap interface, all scrub dead entries first when DiscardInvalid = true:
$map->add($key, $value): mixed     // wraps object in WeakReference; removes old weak entry
$map->remove($key): mixed          // returns dereferenced value (null if GC'd)
$map->removeItem($item): array     // removes all entries matching $item; returns [key => value]
$map->clear(): void                // removes all entries, restarts WeakMap cache, resets $_eventHandlerCount; throws map_readonly on a read-only map with entries
$map->contains($key): bool         // scrubs first, then delegates to parent
$map->itemAt($key): mixed          // scrubs, resolves WeakReference, calls dyNoItem if absent
$map->toArray(): array             // scrubs, resolves all WeakReferences
$map->getCount(): int              // scrubs first
$map->getIterator(): \Iterator     // iterates over toArray()
$map->keyOf($item, bool $multiple = true): mixed  // returns all keys (array) or first key (scalar)
$map->copyFrom($data): void        // clears then adds all from $data
$map->mergeWith($data): void       // adds all from $data, overwriting duplicate keys
```

A read-only map behaves as [TMap](./TMap.md) does, and the failure leaves the map unchanged:

| Call on a read-only map | Result |
|---|---|
| `clear()` or `copyFrom(iterable)` with entries | `map_readonly` |
| `clear()` or `copyFrom([])` without entries | No-op |
| `copyFrom(data)` or `mergeWith(data)` without entries | `map_readonly` from `add()` |
| `mergeWith(data)` with entries | `map_readonly` from `add()` |
| `mergeWith([])`, `copyFrom(null)` | No-op |

`clear()` reads the entry count through `getCount()` on a read-only map, so with `DiscardInvalid` true a map holding only collected values has no entries. A destructor run during `clear()` can remove or add entries; see [TWeakCollectionTrait](./TWeakCollectionTrait.md) "Clear Contract".

## ICollectionFilter — WeakReference Encoding

```php
public static function filterItemForInput(mixed &$item): void
```
Wraps regular objects in `WeakReference`. Skips `Closure`, `IWeakRetainable` (including `TEventHandler`), and already-wrapped `WeakReference` values.

```php
public static function filterItemForOutput(mixed &$item): void
```
Resolves `WeakReference` back to the original object (null if GC'd). Resolves dead `TEventHandler` (no live inner handler) to null.

## Serialization

`_getZappableSleepProps()` excludes the data array (`_d`) and all WeakMap state from serialization — weakly-held values cannot be meaningfully persisted. On `__wakeup()`, the WeakMap cache is re-initialized from scratch if `DiscardInvalid` is true.

`__clone()` clones the internal WeakMap via `weakClone()`.

## Re-entrancy Guard

PHP's cyclic GC can fire destructors between any two opcodes. `scrubWeakReferences()` uses the `isScrubbing()` guard (from [TWeakCollectionTrait](./TWeakCollectionTrait.md)) to prevent a nested destructor call from modifying `$_d` while the outer scrub loop is iterating. Entries skipped by a nested call are cleaned on the next outer pass.

## Patterns & Gotchas

- **TEventHandler tracking** — The inner callable object (not the `TEventHandler` shell) is registered in the WeakMap. A dead inner object causes `filterItemForOutput` to return `null` for the whole entry. A `TEventHandler` whose inner object is collected before it is added calls `weakMarkChanged()`, so the next scrub removes it.
- **Non-object values survive GC** — Scalars, arrays, and null are stored directly; they are never considered "dead" and are never scrubbed.
- **Closures are strongly retained** — If the map is the only reference to a `Closure` it will *not* be GC'd; this matches `TWeakList` / `TWeakCallableCollection` behavior.
- **`DiscardInvalid` lock** — Prefer passing `$discardInvalid` in the constructor if you need a specific mode; changing it later from outside the class throws `TInvalidOperationException`.

## See Also

- [TMap](./TMap.md) — Base map class
- [IWeakCollection](./IWeakCollection.md) — Marker interface for weak collections
- [ICollectionFilter](./ICollectionFilter.md) — Input/output item conversion contract
- [TWeakCollectionTrait](./TWeakCollectionTrait.md) — WeakMap bookkeeping implementation
- [TWeakList](./TWeakList.md) — List variant with weak values
- [TWeakCallableCollection](./TWeakCallableCollection.md) — Priority list of weak callables
