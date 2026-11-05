# Collections/TPriorityPropertyTrait

### Directories
[framework](../INDEX.md) / [Collections](./INDEX.md) / **`TPriorityPropertyTrait`**

## Class Info
**Location:** `framework/Collections/TPriorityPropertyTrait.php`
**Namespace:** `Prado\Collections`
**Since:** 4.3.0

## Overview

Trait that implements the [IPriorityItem](./IPriorityItem.md) interface contract — a single `Priority` property stored as a `?float`. Use this trait together with `implements IPriorityItem` on items that need to carry their own priority when inserted into a [TPriorityList](./TPriorityList.md) or [TPriorityMap](./TPriorityMap.md).

## Usage

```php
class MyItem implements IPriorityItem
{
    use TPriorityPropertyTrait;
    // getPriority() and setPriority() are now provided by the trait.
}
```

When `MyItem` is inserted into a priority collection, the collection calls `getPriority()` to determine the bucket. If the item also implements [IPriorityCapture](./IPriorityCapture.md), the resolved/rounded priority is written back via `setPriority()`.

## Methods

```php
public function getPriority(): ?float
// Returns the stored priority float, or null if none has been set.

public function setPriority(?numeric $value): static
// Sets the priority as a float. Empty string ('') is treated as null (clears priority).
// Returns $this for fluent chaining.

protected function _priorityItemZappableSleepProps(array &$exprops): void
// Excludes $_priority from serialization when it is null (default).
```

## Notes

- `null` means "no explicit priority" — the collection will use its own `DefaultPriority`.
- Priorities are stored as `float` (not string). The collection rounds to its configured precision when inserting.
- `setPriority()` returns `static` for fluent chaining.

## See Also

- [IPriorityItem](./IPriorityItem.md) — Interface this trait implements
- [IPriorityCapture](./IPriorityCapture.md) — Companion interface; allows the collection to write back the resolved priority
- [TPriorityCollectionTrait](./TPriorityCollectionTrait.md) — Collection-side priority management
- [TPriorityList](./TPriorityList.md), [TPriorityMap](./TPriorityMap.md) — Collections that consume IPriorityItem
