# Collections/TPriorityList

### Directories
[framework](../INDEX.md) / [Collections](./INDEX.md) / **`TPriorityList`**

## Class Info
**Location:** `framework/Collections/TPriorityList.php`
**Namespace:** `Prado\Collections`

## Overview
Priority-ordered collections. Every item is assigned a numeric priority (lower number = higher priority). Items are stored internally in priority buckets and flattened into an ordered array on access. A flat array cache (`$_fd`) is invalidated whenever items change.

Default priority: `10`. Configurable decimal precision (default `8`).

## TPriorityList

Extends [TList](./TList.md). Items are ordered by priority, then insertion order within the same priority.

### Constructor

```php
new TPriorityList($data = null, $readOnly = null, $defaultPriority = 10, $precision = 8)
```

### Priority-Specific Methods

```php
$list->add($item);                            // add at default priority (10)
$list->addAtPriority($item, 5.0);            // add at priority 5
$list->insertAt($index, $item);              // insert at flat index
$list->itemsAtPriority(5.0);                 // returns array of items at priority 5
$list->priorityOf($item);                    // returns float priority of item
$list->priorityAt($index);                   // priority at flat index
$list->getDefaultPriority();                 // float, default 10
$list->setDefaultPriority(float $p);
$list->getPriorities();                      // array of all distinct priorities (sorted)
$list->toArrayBelowPriority(float $p, bool $inclusive); // items before cutoff
$list->toArrayAbovePriority(float $p, bool $inclusive); // items after cutoff
```

### Flat vs Bucketed Access

```php
// Flat iteration (all items, priority-ordered):
foreach ($list as $item) {}
$list->toArray();           // flat array

// Bucketed iteration:
$list->toPriorityArray();   // [priority => [items...], ...]
```

## TPriorityMap

Extends [TMap](./TMap.md). Keys are strings; items are ordered by priority on iteration.

```php
$map->add($key, $value);                     // at default priority
$map->add($key, $value, 5.0);               // at priority 5 (third arg)
$map->priorityOf($key);                      // priority of a key
$map->itemsAtPriority(5.0);                  // items at that priority
$map->toPriorityArray();                     // [priority => [key => value, ...], ...]
```

## TPriorityCollectionTrait

Shared implementation for both. Key internal methods:
- `sortPriorities()` — sort the priority buckets
- `flattenPriorities()` — build `$_fd` flat array cache
- `getPriorityCombineStyle()` — `true` = merge items at same priority, `false` = replace

## Patterns & Gotchas

- **Flat cache** (`$_fd`) — invalidated on every mutation. Avoid calling `toArray()` in tight loops after frequent inserts.
- **Priority precision** — stored as float with configurable decimal places (`Precision` property). Use consistent precision to avoid key collisions.
- **Iteration order** — lower priority number comes first (priority 1 before priority 10).
- **`getPriorityCombineStyle()`** — `TPriorityList` uses `true` (merge); `TPriorityMap` uses `false` (replace).
- **Usage in Prado** — event handler lists (`$_e`, `$_ue` in [TComponent](../TComponent.md)) use `TPriorityList`; behavior maps use `TPriorityMap`.
