# Collections/ICollectionFilter

### Directories
[framework](./INDEX.md) / [Collections](./Collections/INDEX.md) / **`ICollectionFilter`**

**Location:** `framework/Collections/ICollectionFilter.php`
**Namespace:** `Prado\Collections`

## Overview
Interface for collections that need to convert items for storage. Primarily used by weak collections ([TWeakCallableCollection](./TWeakCallableCollection.md), [TWeakList](./TWeakList.md)) to convert objects into `WeakReference` for storage and convert them back on retrieval.

## Methods

### filterItemForInput

```php
public static function filterItemForInput(mixed &$item): void
```

Converts an item into an internal storage format. For weak collections, this wraps objects in `WeakReference`.

### filterItemForOutput

```php
public static function filterItemForOutput(mixed &$item): void
```

Converts an item from internal storage format back to its normal state. For weak collections, this unwraps `WeakReference` back to the original object.

## Implementations

- [TWeakList](./TWeakList.md) - Converts objects to WeakReference on input, unwraps on output
- [TWeakCallableCollection](./TWeakCallableCollection.md) - Same weak reference handling for callable collections

## Usage Example

```php
class MyWeakCollection implements ICollectionFilter
{
    public static function filterItemForInput(&$item): void
    {
        if (is_object($item) && !($item instanceof WeakReference)) {
            $item = WeakReference::create($item);
        }
    }
    
    public static function filterItemForOutput(&$item): void
    {
        if ($item instanceof WeakReference) {
            $item = $item->get();
        }
    }
}
```
