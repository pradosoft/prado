# TPriorityMap

### Directories
[./](../INDEX.md) > [Collections](./INDEX.md) > [TPriorityMap](./TPriorityMap.md)

**Location:** `framework/Collections/TPriorityMap.php`
**Namespace:** `Prado\Collections`

## Overview

TPriorityMap implements a key-value collection with priority ordering. Keys are preserved but iteration occurs in priority order.

## Inheritance

Extends [TMap](./TMap.md) and implements [IPriorityCollection](./IPriorityCollection.md).

## Key Features

- Key-value pairs with priority ordering
- Backward compatible with TMap
- Lower priority number = higher priority in iteration

## Usage

```php
$map = new TPriorityMap();
$map->add('key1', 'value1', 10);  // Default priority
$map->add('key2', 'value2', 5);   // Higher priority (processed first)
$map->add('key3', 'value3', 15);  // Lower priority

foreach ($map as $key => $value) {
    // Order: key2, key1, key3
}
```

## Constructor

```php
public function __construct(
    $data = null,
    ?bool $readOnly = null,
    ?numeric $defaultPriority = null,
    ?int $precision = null
)
```

## Methods

### add

```php
public function add(mixed $key, mixed $value, ?numeric $priority = null): void
```

Adds a key-value pair with optional priority.

### priorityAt

```php
public function priorityAt(mixed $key): false|numeric
```

Gets the priority of an item by key.

## See Also

- [TMap](./TMap.md) - Base map class
- [TPriorityList](./TPriorityList.md) - List variant with priority
- [IPriorityCollection](./IPriorityCollection.md) - Priority collection interface
