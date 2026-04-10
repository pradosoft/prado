# Collections/IPriorityItem

### Directories
[framework](./INDEX.md) / [Collections](./Collections/INDEX.md) / **`IPriorityItem`**

**Location:** `framework/Collections/IPriorityItem.php`
**Namespace:** `Prado\Collections`

## Overview
Interface for objects that can specify their own priority when added to a priority collection. Objects implementing this interface can self-prioritize in [TPriorityList](./TPriorityList.md) or [TPriorityMap](./TPriorityMap.md).

## Methods

### getPriority

```php
public function getPriority(): numeric
```

Returns the priority of the item. Lower numbers indicate higher priority.

## Usage

When an object implementing `IPriorityItem` is added to a priority collection, the collection queries this method to determine the item's priority:

```php
class MyItem implements IPriorityItem
{
    private float $priority = 10;
    
    public function getPriority(): float
    {
        return $this->priority;
    }
    
    public function setPriority(float $priority): void
    {
        $this->priority = $priority;
    }
}

$list = new TPriorityList();
$item = new MyItem();
$item->setPriority(5); // Higher priority
$list->add($item);
```

## Related Interfaces

- [IPriorityCollection](./IPriorityCollection.md) - The collection interface
- [IPriorityCapture](./IPriorityCapture.md) - For capturing priority when items are added
- [IPriorityProperty](./IPriorityProperty.md) - Combined getter/setter interface
