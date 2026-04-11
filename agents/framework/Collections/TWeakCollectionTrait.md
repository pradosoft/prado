# Collections/TWeakCollectionTrait

### Directories
[framework](../INDEX.md) / [Collections](./INDEX.md) / **`TWeakCollectionTrait`**

## Class Info
**Location:** `framework/Collections/TWeakCollectionTrait.php`
**Namespace:** `Prado\Collections`

## Overview
Trait providing WeakMap-based caching for weak collections. Tracks objects using PHP's WeakMap and automatically detects when referenced objects are garbage collected.

## Usage

Classes using this trait get automatic weak reference tracking:

```php
class MyWeakCollection
{
    use TWeakCollectionTrait;
    
    public function addItem($item): void
    {
        $this->weakStart();
        $this->weakAdd($item);
    }
}
```

## Key Methods

### weakStart

```php
protected function weakStart(): void
```

Initializes a new WeakMap.

### weakRestart

```php
protected function weakRestart(): void
```

Resets the WeakMap if it exists.

### weakClone

```php
protected function weakClone(): void
```

Clones the WeakMap when the collection is cloned.

### weakStop

```php
protected function weakStop(): void
```

Stops tracking by nullifying the WeakMap.

### weakChanged

```php
protected function weakChanged(): bool
```

Returns true if the WeakMap count differs from expected (objects were garbage collected).

### weakResetCount

```php
protected function weakResetCount(): void
```

Resets the expected count to match current WeakMap count.

### weakCount

```php
protected function weakCount(): ?int
```

Returns the number of tracked objects in the WeakMap.

## Properties

- `$_weakMap` - The WeakMap instance
- `$_weakCount` - Expected count of tracked objects

## Notes

- Uses PHP's WeakMap (available in PHP 8.0+)
- WeakMap automatically removes entries when objects are garbage collected
- When weakChanged() returns true, collection can scrub invalid entries

## See Also

- [TWeakList](./TWeakList.md) - List using this trait
- [TWeakCallableCollection](./TWeakCallableCollection.md) - Callable collection using this trait
- `WeakReference` - PHP's weak reference class
