# TPriorityCollectionTrait

### Directories
[./](../INDEX.md) > [Collections](./INDEX.md) > [TPriorityCollectionTrait](./TPriorityCollectionTrait.md)

**Location:** `framework/Collections/TPriorityCollectionTrait.php`
**Namespace:** `Prado\Collections`

## Overview

Trait providing common functionality for priority collections. Implements the logic for managing priorities, sorting, and flattening priority-ordered data.

## Usage

Classes using this trait must implement `getPriorityCombineStyle()`:

```php
class MyPriorityList extends TList
{
    use TPriorityCollectionTrait;
    
    private function getPriorityCombineStyle(): bool
    {
        return true;  // true = merge (list), false = replace (map)
    }
}
```

## Key Methods

### getDefaultPriority / setDefaultPriority

```php
public function getDefaultPriority(): numeric
public function setDefaultPriority($value): void
```

Gets or sets the default priority for items without specified priorities. Default is `10`.

### getPrecision / setPrecision

```php
public function getPrecision(): int
public function setPrecision(int $value): void
```

Gets or sets the precision of numeric priorities. Default is `8` decimal places.

### getPriorities

```php
public function getPriorities(): array
```

Returns all priorities in the collection.

### itemsAtPriority

```php
public function itemsAtPriority(numeric $priority): array
```

Gets all items at a given priority level.

### flattenPriorities

```php
protected function flattenPriorities(): void
```

Flattens priority items into a cached ordered array (`$_fd`).

## Properties

- `$_o` - Whether the data array is currently ordered
- `$_fd` - Cached flattened array
- `$_dp` - Default priority
- `$_p` - Precision

## See Also

- [TPriorityList](./TPriorityList.md) - List using this trait
- [TPriorityMap](./TPriorityMap.md) - Map using this trait
