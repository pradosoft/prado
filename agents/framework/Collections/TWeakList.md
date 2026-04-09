# TWeakList

### Directories
[./](../INDEX.md) > [Collections](./INDEX.md) > [TWeakList](./TWeakList.md)

**Location:** `framework/Collections/TWeakList.php`
**Namespace:** `Prado\Collections`

## Overview

TWeakList implements an integer-indexed collection that stores objects as WeakReference. Objects that are garbage collected are automatically removed from the list.

## Inheritance

Extends [TList](./TList.md) and implements [IWeakCollection](./IWeakCollection.md), [ICollectionFilter](./ICollectionFilter.md).

## Key Features

- Objects stored as WeakReference (don't prevent garbage collection)
- Automatic cleanup when objects are invalidated
- Closures stored directly (not as WeakReference)
- IWeakRetainable objects retained with full reference

## Usage

```php
$list = new TWeakList();
$list->add(new MyClass());  // Stored as WeakReference
$list->add($object);
$list->add(function() {});  // Closure stored directly

// When $object is garbage collected elsewhere,
// it's automatically removed from the list
```

## Constructor

```php
public function __construct(
    $data = null,
    ?bool $readOnly = null,
    ?bool $discardInvalid = null
)
```

- `$discardInvalid` - If true (default), invalid weak references are removed. If false, null is returned for invalid references.

## Key Differences from TList

- Objects stored as WeakReference internally
- Retrieving an object that's been garbage collected returns null
- Closures and IWeakRetainable objects stored directly

## Filtering

Implements [ICollectionFilter](./ICollectionFilter.md):
- `filterItemForInput()` - Wraps objects in WeakReference
- `filterItemForOutput()` - Unwraps WeakReference to get object

## See Also

- [TList](./TList.md) - Base list class
- [IWeakCollection](./IWeakCollection.md) - Weak collection interface
- [IWeakRetainable](./IWeakRetainable.md) - Objects to retain fully
- [ICollectionFilter](./ICollectionFilter.md) - Item conversion interface
- [TWeakCollectionTrait](./TWeakCollectionTrait.md) - WeakMap implementation
