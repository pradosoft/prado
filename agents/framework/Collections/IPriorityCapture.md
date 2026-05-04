# Collections/IPriorityCapture

### Directories
[framework](./INDEX.md) / [Collections](./Collections/INDEX.md) / **`IPriorityCapture`**

**Location:** `framework/Collections/IPriorityCapture.php`
**Namespace:** `Prado\Collections`

## Overview
Interface for objects that can have their priority captured when added to priority collections. Used by [TPriorityList](./TPriorityList.md) and [TPriorityMap](./TPriorityMap.md) to set priority on items that support it.

## Methods

### setPriority

```php
public function setPriority(numeric $value): void
```

Sets the priority of the item. Lower numbers indicate higher priority.

## Usage

```php
class MyItem implements IPriorityCapture, IPriorityItem
{
    private float $priority = 10;
    
    public function getPriority(): float
    {
        return $this->priority;
    }
    
    public function setPriority($value): void
    {
        $this->priority = (float) $value;
    }
}
```

## See Also

- [IPriorityItem](./IPriorityItem.md) - For getting the priority
- [IPriorityProperty](./IPriorityProperty.md) - Combined interface
