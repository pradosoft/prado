# Collections/IPriorityCollection

### Directories
[framework](./INDEX.md) / [Collections](./Collections/INDEX.md) / **`IPriorityCollection`**

**Location:** `framework/Collections/IPriorityCollection.php`
**Namespace:** `Prado\Collections`

## Overview
Interface for priority-ordered collections. Implemented by [TPriorityList](./TPriorityList.md) and [TPriorityMap](./TPriorityMap.md) to provide common priority collection functionality.

## Methods

### getDefaultPriority

```php
public function getDefaultPriority(): numeric
```

Returns the default priority assigned to items that don't specify a priority. Default is typically `10`.

### getPrecision

```php
public function getPrecision(): int
```

Returns the precision of numeric priorities. Default is typically `8` decimal places.

### priorityAt

```php
public function priorityAt(mixed $key): false|numeric
```

Returns the priority of an item at a particular key/index. Returns `false` if the item is not found.

## Implementations

- [TPriorityList](./TPriorityList.md) - Integer-indexed collection with priority ordering
- [TPriorityMap](./TPriorityMap.md) - Key-value collection with priority ordering

## Related Interfaces

- [IPriorityItem](./IPriorityItem.md) - For objects that self-prioritize
- [IPriorityCapture](./IPriorityCapture.md) - For capturing priority when items are added
- [IPriorityProperty](./IPriorityProperty.md) - Combined getter/setter interface

## See Also

- [TPriorityCollectionTrait](./TPriorityCollectionTrait.md) - Trait implementing this interface
