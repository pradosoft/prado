# Collections/TWeakCollectionTrait

### Directories
[framework](../INDEX.md) / [Collections](./INDEX.md) / **`TWeakCollectionTrait`**

## Class Info
**Location:** `framework/Collections/TWeakCollectionTrait.php`
**Namespace:** `Prado\Collections`
**Since:** 4.3.0

## Overview

Shared WeakMap bookkeeping for all weak collections. Tracks how many times each object is stored, detects GC events by comparing the live WeakMap count to the expected count (`$_weakCount`), and provides utilities for starting, stopping, resetting, and cloning the WeakMap.

## Internal State

| Field | Type | Description |
|---|---|---|
| `$_weakMap` | `?WeakMap` | The PHP 8 WeakMap tracking stored objects. `null` when not active. |
| `$_weakCount` | `int` | Number of distinct objects known to be in the collection. Compared against `$_weakMap->count()` to detect GC. |
| `$_scrubbing` | `bool` | Re-entrancy guard for `scrubWeakReferences()`. `true` while a scrub loop is executing. |
| `$_discardInvalid` | `?bool` | Whether dead entries are automatically removed. `null` = lazy-initialized. |
| `$_eventHandlerCount` | `int` | Number of `TEventHandler` items currently tracked; used to skip TEventHandler search passes when zero. |

## Protected Methods

```php
// WeakMap lifecycle:
protected function weakStart(): void        // create a new WeakMap
protected function weakRestart(): void      // replace with a fresh WeakMap (clear all entries)
protected function weakClone(): void        // clone the WeakMap (call in __clone)
protected function weakStop(): void         // set WeakMap to null (disable tracking)

// Change detection:
protected function weakChanged(): bool      // true if WeakMap count != $_weakCount (GC occurred)
protected function weakResetCount(): void   // sync $_weakCount to current WeakMap count (call after scrub)

// Object registration:
protected function weakAdd(object $object): int     // increment count; adds to WeakMap. Returns new count.
protected function weakRemove(object $object): int  // decrement count; removes from WeakMap. Returns remaining count.
protected function weakObjectCount(object $object): ?int  // instance count for one object
protected function weakCount(): ?int                // total distinct objects in WeakMap

// Re-entrancy guard (since 4.3.3):
protected function isScrubbing(): bool      // true while scrubWeakReferences() is executing
protected function setScrubbing(bool $value): void  // set/clear the re-entrancy guard

// Serialization:
protected function _weakZappableSleepProps(array &$exprops): void
// Appends to $exprops: $_weakMap, $_weakCount, $_scrubbing, $_eventHandlerCount (always excluded).
// Also excludes $_discardInvalid when null (lazy-init default).
```

## Usage Pattern

Implementing classes:

1. Call `weakStart()` when `DiscardInvalid` is set to `true`.
2. Call `weakAdd($object)` / `weakRemove($object)` (or the custom variants `weakCustomAdd` / `weakCustomRemove`) whenever an object is inserted or removed.
3. Before any read or write, call `scrubWeakReferences()` (implemented in the using class), which checks `isScrubbing()`, `getDiscardInvalid()`, and `weakChanged()` before proceeding.
4. After a scrub loop, call `weakResetCount()`.
5. In `__clone()`, call `weakClone()`. In `__wakeup()`, call `weakStart()` if `DiscardInvalid` is true.

## Re-entrancy Guard (since 4.3.3)

PHP's cyclic GC can fire destructors between any two opcodes, potentially calling back into `scrubWeakReferences()` while it is already executing. The `isScrubbing()` / `setScrubbing()` guard prevents the inner call from modifying the internal array while the outer loop is iterating. Any entries skipped by the inner call are cleaned on the next outer pass.

## Serialization

`_weakZappableSleepProps()` excludes all WeakMap runtime state. WeakReferences do not survive serialization; weak collections effectively wake up empty with no tracked objects.

## See Also

- [TWeakList](./TWeakList.md) — Uses this trait
- [TWeakCallableCollection](./TWeakCallableCollection.md) — Uses this trait
- [TWeakMap](./TWeakMap.md) — Uses this trait
- [IWeakCollection](./IWeakCollection.md) — Marker interface for all weak collections
