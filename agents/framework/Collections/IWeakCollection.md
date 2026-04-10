# Collections/IWeakCollection

### Directories
[framework](./INDEX.md) / [Collections](./Collections/INDEX.md) / **`IWeakCollection`**

**Location:** `framework/Collections/IWeakCollection.php`
**Namespace:** `Prado\Collections`

## Overview
Marker interface for weak collections. Implemented by collections that use `WeakReference` to store objects without preventing garbage collection.

## Implementations

- [TWeakCallableCollection](./TWeakCallableCollection.md) - Collection of callable objects using weak references
- [TWeakList](./TWeakList.md) - List using weak references

## Key Features

Objects stored in weak collections are held via `WeakReference`. When an object is no longer referenced elsewhere in the application and is garbage collected, the weak collection automatically removes it.

## See Also

- [IWeakRetainable](./IWeakRetainable.md) - Marker for objects that should be retained (not stored as WeakReference)
- [ICollectionFilter](./ICollectionFilter.md) - For item conversion on input/output
- [TWeakCollectionTrait](./TWeakCollectionTrait.md) - Implementation trait for weak collections
