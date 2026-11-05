# Collections/TPriorityMap

### Directories
[framework](../INDEX.md) / [Collections](./INDEX.md) / **`TPriorityMap`**

## Class Info
**Location:** `framework/Collections/TPriorityMap.php`
**Namespace:** `Prado\Collections`

## Overview

TPriorityMap implements a key-value collection where each entry has a numeric priority. Keys are string/integer (no duplicates allowed regardless of priority); iteration and flattening happen in priority order (lower number = higher priority). Backward compatible with [TMap](./TMap.md).

## Inheritance & Interfaces

Extends [TMap](./TMap.md). Implements [IPriorityCollection](./IPriorityCollection.md). Uses [TPriorityCollectionTrait](./TPriorityCollectionTrait.md).

## Constructor

```php
new TPriorityMap(
    array|\Traversable|TPriorityList|TPriorityMap|null $data = null,
    ?bool    $readOnly        = null,
    ?numeric $defaultPriority = null,  // default 10
    ?int     $precision       = null   // default 8
)
```

## Usage

```php
$map = new TPriorityMap();
$map->add('key1', 'value1');       // at default priority (10)
$map->add('key2', 'value2', 5);    // higher priority (comes first)
$map->add('key3', 'value3', 15);   // lower priority (comes last)

foreach ($map as $key => $value) {
    // Iteration order: key2, key1, key3
}

$map->toPriorityArray();
// ['5' => ['key2' => 'value2'], '10' => ['key1' => 'value1'], '15' => ['key3' => 'value3']]
```

## Key Methods

```php
$map->add($key, $value, $priority = null): mixed   // adds/replaces; returns key used
$map->remove($key, $priority = null): mixed         // removes and returns old value
$map->itemAt($key, $priority = false): mixed        // $priority=false searches all priorities
$map->contains($key): bool

$map->priorityAt($key): false|numeric               // priority of a key
$map->setPriorityAt($key, $priority = null): numeric // move key to new priority; returns old priority
$map->priorityOf($item): false|numeric              // priority of an item value

$map->getKeys(): array                              // all keys in priority order
$map->getNextIntegerKey(): int                      // next auto-assigned integer key
$map->getPriorities(): array                        // all distinct priority strings, sorted
$map->getPriorityCount($priority = null): int
$map->itemsAtPriority($priority = null): ?array     // [key => value, ...] at that priority

$map->toArray(): array                              // flat [key => value] in priority order
$map->toPriorityArray(): array                      // [priority => [key => value, ...], ...]
$map->toArrayBelowPriority($priority, bool $inclusive = false): array
$map->toArrayAbovePriority($priority, bool $inclusive = true): array
```

## getPriorityCombineStyle

Returns `false` (replace style). When flattening, higher-priority buckets' keys are overwritten by lower-priority buckets if they share a key — the lower-priority (later) definition wins in the flat map. (Compare [TPriorityList](./TPriorityList.md) which uses `true` for merge/append style.)

## Dynamic Method Hooks (via behaviors)

```
dyAddItem(mixed $key, mixed $value)
dyRemoveItem(mixed $key, mixed $value)
dyNoItem(mixed $returnValue, mixed $key): mixed
```

## Internal State

- `$_c` — item count (maintained separately from `$_d` because `$_d` is bucketed).
- `$_ic` — next auto-assigned integer key for `null`-key adds.
- `$_d` — `array<priority_string, array<key, value>>` (bucketed storage).
- `$_fd` — flattened cache; `null` when stale.

## Patterns & Gotchas

- **No duplicate keys** — Adding a key that already exists at a different priority removes the old entry and re-inserts at the new priority.
- **Standard array access uses default priority** — `$map[$key] = $value` always adds at the default priority.
- **IPriorityItem / IPriorityCapture** — items implementing [IPriorityItem](./IPriorityItem.md) supply their own priority via `getPriority()`; items implementing [IPriorityCapture](./IPriorityCapture.md) receive the resolved priority back via `setPriority()`.

## See Also

- [TMap](./TMap.md) — Base map class
- [TPriorityList](./TPriorityList.md) — Integer-indexed variant with priority ordering
- [TPriorityCollectionTrait](./TPriorityCollectionTrait.md) — Shared priority logic
- [IPriorityCollection](./IPriorityCollection.md), [IPriorityItem](./IPriorityItem.md), [IPriorityCapture](./IPriorityCapture.md)
