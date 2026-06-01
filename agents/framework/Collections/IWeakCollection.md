# Collections/IWeakCollection

### Directories
[framework](../INDEX.md) / [Collections](./INDEX.md) / **`IWeakCollection`**

## Class Info
**Location:** `framework/Collections/IWeakCollection.php`
**Namespace:** `Prado\Collections`
**Since:** 4.3.0

## Overview

Empty marker interface identifying collections that store object values as `WeakReference` (or track them via a WeakMap). No methods are required. Code that needs to detect a weak collection can check `instanceof IWeakCollection`.

## Implementations

- [TWeakCallableCollection](./TWeakCallableCollection.md) — Priority-ordered list of weak callables (event handler backing store)
- [TWeakList](./TWeakList.md) — Integer-indexed list with weak object values
- [TWeakMap](./TWeakMap.md) — Key-value map with weak object values (since 4.3.3)

## See Also

- [IWeakRetainable](./IWeakRetainable.md) — Marker for objects that must be stored directly (not weakened)
- [ICollectionFilter](./ICollectionFilter.md) — Input/output item conversion contract
- [TWeakCollectionTrait](./TWeakCollectionTrait.md) — Shared WeakMap bookkeeping for all implementations
