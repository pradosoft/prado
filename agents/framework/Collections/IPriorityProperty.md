# IPriorityProperty

### Directories
[./](../INDEX.md) > [Collections](./INDEX.md) > [IPriorityProperty](./IPriorityProperty.md)

**Location:** `framework/Collections/IPriorityProperty.php`
**Namespace:** `Prado\Collections`

## Overview

Combined interface for objects that have both getting and setting of priority. Extends both [IPriorityItem](./IPriorityItem.md) and [IPriorityCapture](./IPriorityCapture.md).

## Interface Hierarchy

```
IPriorityProperty
    extends IPriorityItem
    extends IPriorityCapture
```

## Methods

### getPriority

```php
public function getPriority(): numeric
```

Returns the priority of the item.

### setPriority

```php
public function setPriority(numeric $value): void
```

Sets the priority of the item.

## Implementation

[TPriorityPropertyTrait](./TPriorityPropertyTrait.md) provides a standard implementation of this interface.

## See Also

- [IPriorityItem](./IPriorityItem.md) - For getting priority
- [IPriorityCapture](./IPriorityCapture.md) - For setting priority
- [TPriorityPropertyTrait](./TPriorityPropertyTrait.md) - Standard implementation trait
