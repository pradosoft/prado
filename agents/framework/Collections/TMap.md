# Collections/TMap

### Directories
[framework](../INDEX.md) / [Collections](./INDEX.md) / **`TMap`**

## Class Info
**Location:** `framework/Collections/TMap.php`
**Namespace:** `Prado\Collections`

## Overview
`TMap` is the framework's general-purpose key-value collection. It extends [TComponent](../TComponent.md) (giving it behavior/event support) and implements `IteratorAggregate`, `ArrayAccess`, and `Countable`. Keys can be any integer or string; values can be anything. It is the base class for [TPriorityMap](./TPriorityMap.md), [TAttributeCollection](./TAttributeCollection.md), and other specialized maps.

## Interfaces Implemented

- `IteratorAggregate` — `foreach` support via `ArrayIterator`
- `ArrayAccess` — bracket syntax (`$map[$key]`, `isset($map[$key])`, `unset($map[$key])`)
- `Countable` — `count($map)`

## Key Properties

| Property | Type | Description |
|---|---|---|
| `ReadOnly` | `bool` | When true, all mutations throw `TInvalidOperationException`. Defaults to `false` (`null` until first access). |

### Internal Storage

- `$_d` (`array`) — protected; direct access is allowed in subclasses. Keyed by int or string.
- `$_r` (`?bool`) — private read-only flag. `null` means "not yet set" — allows one-time initialization.

## Key Methods

### Construction

```php
public function __construct($data = null, $readOnly = null)
```

- `$data`: optional array or `Traversable` to populate the map immediately via `copyFrom()`.
- `$readOnly`: if `$data` is provided and `$readOnly` is truthy, the map is locked after initial load.
- Passing `null` for `$readOnly` leaves the flag in its unset (`null`) state; it collapses to `false` on first mutation.

### Read Operations

```php
public function itemAt($key): mixed           // returns null if key absent; triggers dyNoItem behavior hook
public function contains($key): bool          // checks key existence (handles null values via array_key_exists)
public function getKeys(): array              // returns array_keys($this->_d)
public function getCount(): int               // same as count()
public function toArray(): array              // returns internal array directly (no copy)
public function keyOf($item, bool $multiple = true): mixed
    // $multiple=true  → returns assoc array of [key => item] for all matches
    // $multiple=false → returns first matching key via array_search, or false
```

### Mutation

```php
public function add($key, $value): mixed
    // Adds/overwrites. null $key appends (like $arr[] = $v) and returns auto-assigned key.
    // Fires dyAddItem($key, $value) behavior hook.

public function remove($key): mixed           // Returns removed value or null. Fires dyRemoveItem($key, $value).
public function removeItem(mixed $item): array // Removes ALL keys mapping to $item; returns [key => value] array.
public function clear(): void                 // Iterates and calls remove() for each key (fires hooks for each).
public function copyFrom($data): void         // Clears first, then adds all. Accepts array or Traversable.
public function mergeWith($data): void        // Adds/overwrites without clearing first. Accepts array or Traversable.
```

### Read-Only Management

```php
public function getReadOnly(): bool
public function setReadOnly($value)           // Can only be set once; subsequent calls throw unless Prado::isCallingSelf()
protected function collapseReadOnly(): void   // Called at start of add(); freezes null → false
```

`setReadOnly` enforces single-assignment semantics: once set to a non-null value, only internal framework code (via `Prado::isCallingSelf()`) can change it.

## Dynamic Events (Behavior Hooks)

Declared via `@method` PHPDoc — these fire on attached behaviors:

| Method | When |
|---|---|
| `dyAddItem($key, $value)` | After an item is successfully added |
| `dyRemoveItem($key, $value)` | After an item is successfully removed |
| `dyNoItem($returnValue, $key)` | When `itemAt()` finds no entry for the key; return value overrides the null default |

## ArrayAccess Mapping

| PHP syntax | Delegates to |
|---|---|
| `$map[$key]` (read) | `itemAt($key)` |
| `$map[$key] = $v` | `add($key, $v)` |
| `isset($map[$key])` | `contains($key)` |
| `unset($map[$key])` | `remove($key)` |
| `$map[] = $v` | `add(null, $v)` — appends |

## Serialization

`_getZappableSleepProps()` excludes `$_d` when empty and `$_r` when it is `null` (never set). Subclasses that add fields should call `parent::_getZappableSleepProps($exprops)` first.

## Patterns & Gotchas

- **`null` key handling** — Passing `null` as the key to `add()` triggers PHP's array-append behavior. The returned value is the auto-assigned integer key.
- **`contains()` vs. `isset()`** — `contains()` uses both `isset()` and `array_key_exists()` to correctly detect keys with `null` values. Use `contains()`, not `isset($map[$key])`, when null values are possible.
- **`toArray()` returns by value** — The internal array is copied on return; modifying the result does not affect the map.
- **`removeItem()` is a full scan** — It iterates `toArray()` and removes every matching key; O(n).
- **`keyOf()` with `$multiple=false`** — Uses strict `array_search`; returns `false` (not `null`) when not found.
- **Read-only lock is one-way** — Once `_r` is set to `true` or `false`, it cannot be changed by user code. The constructor can set it to `false` (via `copyFrom` triggering `collapseReadOnly`) before `setReadOnly` is called with a non-null value.
- **Subclassing** — Override `add()` and `remove()` for custom behavior, but always call `parent::add()`/`parent::remove()` to fire the `dy` hooks. For priority-aware behavior, extend [TPriorityMap](./TPriorityMap.md) instead.
- **`clear()` fires hooks** — Because `clear()` calls `remove()` for each key, `dyRemoveItem` fires once per item. This may matter for attached behaviors.
- **Behavior on read-only `remove()`** — Unlike `add()`, `remove()` does not call `collapseReadOnly()` first. It checks `$this->_r` directly. A map with `_r === null` will not throw on `remove()` — this is intentional for the uninitialized state.
