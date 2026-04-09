# IWeakRetainable

### Directories
[./](../INDEX.md) > [Collections](./INDEX.md) > [IWeakRetainable](./IWeakRetainable.md)

**Location:** `framework/Collections/IWeakRetainable.php`
**Namespace:** `Prado\Collections`

## Overview

Marker interface for objects that should be retained (not stored as WeakReference) in weak collections. Objects implementing this interface will be kept with full reference in [TWeakList](./TWeakList.md), [TWeakCallableCollection](./TWeakCallableCollection.md), and [TEventHandler](../TEventHandler.md).

## Usage

```php
class MyRetainableObject implements IWeakRetainable
{
    // This object will not be stored as WeakReference
    // It will be retained even if no other references exist
}
```

## Common Implementations

- [TEventHandler](../TEventHandler.md) - Event handlers are retained to ensure callbacks still work
- Any object that should never be garbage collected while in a weak collection

## See Also

- [IWeakCollection](./IWeakCollection.md) - The weak collection interface
- [TWeakList](./TWeakList.md) - List using weak references
- [TWeakCallableCollection](./TWeakCallableCollection.md) - Callable collection using weak references
