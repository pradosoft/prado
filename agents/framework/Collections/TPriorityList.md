# Collections/TPriorityList

### Directories
[framework](../INDEX.md) / [Collections](./INDEX.md) / **`TPriorityList`**

## Class Info
**Location:** `framework/Collections/TPriorityList.php`
**Namespace:** `Prado\Collections`

## Overview
Priority-ordered integer-indexed collection. Every item is assigned a numeric priority (lower number = higher priority). Items are stored internally in priority buckets and flattened into an ordered array on access. A flat array cache (`$_fd`) is invalidated whenever items change.

Default priority: `10`. Configurable decimal precision (default `8`). Precision and DefaultPriority are locked after initial construction — changing them externally throws `TInvalidOperationException`.

## Inheritance & Interfaces

Extends [TList](./TList.md). Implements [IPriorityCollection](./IPriorityCollection.md). Uses [TPriorityCollectionTrait](./TPriorityCollectionTrait.md).

## Constructor

```php
new TPriorityList(
    array|\Iterator|null $data = null,
    ?bool   $readOnly        = null,
    ?numeric $defaultPriority = null,  // default 10
    ?int     $precision       = null   // default 8
)
```

## Priority-Specific Methods

```php
$list->add($item);                                   // add at default priority (10)
$list->insertAtIndexInPriority($item, $index, $priority); // core insertion method
$list->insertAt($index, $item);                      // insert at flat index, preserves priority
$list->itemAt($index): mixed                         // item at flat index
$list->itemAtIndexInPriority($index, $priority): mixed
$list->itemsAtPriority($priority = null): ?array     // items at a given priority bucket
$list->priorityOf($item): array|false|numeric        // priority of item (or with index)
$list->priorityAt($index): numeric                   // priority at flat index
$list->getDefaultPriority(): numeric                 // default 10
$list->getPrecision(): int                           // default 8
$list->getPriorities(): array                        // all distinct priorities, sorted low→high
$list->getPriorityCount($priority = null): int       // items at that priority
$list->toArrayBelowPriority($priority, bool $inclusive = false): array
$list->toArrayAbovePriority($priority, bool $inclusive = true): array
```

## Flat vs Bucketed Access

```php
// Flat iteration (all items, priority-ordered):
foreach ($list as $item) {}
$list->toArray();           // flat array (uses $_fd cache)

// Bucketed iteration:
$list->toPriorityArray();   // [priority_string => [items...], ...]
```

## Extension Points

Override `insertAtIndexInPriority()` and `removeAtIndexInPriority()` in subclasses (always call `parent::`). Do not override `add()` or `remove()` directly.

## getPriorityCombineStyle

Returns `true` (merge style). This means items at the same priority are concatenated when the flat cache is built. (Compare [TPriorityMap](./TPriorityMap.md) which returns `false` for replace style.)

## Patterns & Gotchas

- **Flat cache** (`$_fd`) — invalidated on every mutation. Avoid calling `toArray()` in tight loops after frequent inserts.
- **Priority precision** — stored as string-keyed floats rounded to `Precision` decimal places. Use consistent precision across operations to avoid bucket mismatches.
- **Iteration order** — lower priority number comes first (priority 1 before priority 10).
- **Usage in Prado** — event handler lists (`$_e`, `$_ue` in [TComponent](../TComponent.md)) use `TPriorityList`; behavior maps use [TPriorityMap](./TPriorityMap.md). The weak-callable variant is [TWeakCallableCollection](./TWeakCallableCollection.md).
- **IPriorityItem / IPriorityCapture** — if an inserted item implements [IPriorityItem](./IPriorityItem.md), its `getPriority()` is used as the priority. If it implements [IPriorityCapture](./IPriorityCapture.md), the resolved priority is written back via `setPriority()`.

## See Also

- [TPriorityMap](./TPriorityMap.md) — Map variant with priority ordering
- [TPriorityCollectionTrait](./TPriorityCollectionTrait.md) — Shared priority logic
- [TWeakCallableCollection](./TWeakCallableCollection.md) — Weak-reference variant for callables
- [IPriorityCollection](./IPriorityCollection.md), [IPriorityItem](./IPriorityItem.md), [IPriorityCapture](./IPriorityCapture.md)
