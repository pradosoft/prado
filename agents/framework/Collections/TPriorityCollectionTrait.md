# Collections/TPriorityCollectionTrait

### Directories
[framework](../INDEX.md) / [Collections](./INDEX.md) / **`TPriorityCollectionTrait`**

## Class Info
**Location:** `framework/Collections/TPriorityCollectionTrait.php`
**Namespace:** `Prado\Collections`
**Since:** 4.3.0

## Overview

Shared trait implementing priority management for [TPriorityList](./TPriorityList.md) and [TPriorityMap](./TPriorityMap.md). Handles priority storage, sorting, flattening to a cache, and the above/below-priority slice methods. Priorities are represented internally as numeric strings rounded to a configurable precision.

## Required Method

Any class using this trait **must** provide a private `getPriorityCombineStyle(): bool`:

```php
private function getPriorityCombineStyle(): bool
{
    return true;   // true  = array_merge  (list style — TPriorityList)
                   // false = array_replace (map style — TPriorityMap)
}
```

## Internal State

| Field | Type | Description |
|---|---|---|
| `$_o` | `bool` | Whether `$_d` buckets are currently sorted by priority key. |
| `$_fd` | `?array` | Flattened cache; `null` when stale (invalidated on every mutation). |
| `$_dp` | `?string` | Default priority as a string. Lazy-initialized to `'10'`. |
| `$_p` | `?int` | Precision (decimal places). Lazy-initialized to `8`. |

## Public Methods

```php
$col->getDefaultPriority(): numeric    // default '10'
// setDefaultPriority is locked after construction; only callable by self/subclass.

$col->getPrecision(): int              // default 8
// setPrecision is locked after construction; only callable by self/subclass.
// Changing precision re-rounds all existing priority keys and clears $_fd.

$col->getPriorities(): array           // all distinct priority strings, sorted low→high
$col->getPriorityCount($priority = null): int   // items at a priority bucket
$col->itemsAtPriority($priority = null): ?array // raw bucket contents (not filtered)

$col->toArray(): array                 // flattened (uses $_fd cache)
$col->toPriorityArray(): array         // [priority_string => [items...], ...]
$col->toArrayBelowPriority($priority, bool $inclusive = false): array
$col->toArrayAbovePriority($priority, bool $inclusive = true): array

$col->getIterator(): \Iterator         // iterates the flattened cache
```

## Protected Helpers

```php
protected function ensurePriority($priority): string
// Normalizes input to a rounded priority string; uses DefaultPriority when null.

protected function sortPriorities(): void
// ksort($_d, SORT_NUMERIC); sets $_o = true. No-op if already sorted.

protected function flattenPriorities(): void
// Builds $_fd from $_d using array_merge (list) or array_replace (map). No-op if cache valid.

protected function _priorityZappableSleepProps(&$exprops): void
// Excludes $_fd always, and $_o / $_dp / $_p when they are at default values.
```

## Serialization

`_priorityZappableSleepProps()` excludes `$_fd` (always — it is a cache) and excludes `$_o`, `$_dp`, `$_p` when they hold their null/default lazy-init values to minimize serialized state.

## Patterns & Gotchas

- **Priority strings** — Priorities are stored as string array keys (result of `(string) round(...)`) not as floats. Never assume integer keys when iterating `$_d`.
- **Lock on Precision / DefaultPriority** — Both properties throw `TInvalidOperationException` if changed after initial construction by external callers (`Prado::isCallingSelf()` guard). Set them only in the constructor.
- **`$_fd` invalidation** — The cache must be set to `null` after any mutation. `flattenPriorities()` rebuilds it lazily on next read.
- **Combine style** — `true` (merge) means duplicate-priority items stack in insertion order (list behavior). `false` (replace) means a later bucket's keys overwrite earlier ones (map behavior).

## See Also

- [TPriorityList](./TPriorityList.md) — Uses this trait (combine style: merge)
- [TPriorityMap](./TPriorityMap.md) — Uses this trait (combine style: replace)
- [TWeakCallableCollection](./TWeakCallableCollection.md) — Priority list with weak references
- [IPriorityCollection](./IPriorityCollection.md), [IPriorityItem](./IPriorityItem.md), [IPriorityCapture](./IPriorityCapture.md)
