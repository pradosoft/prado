# Collections/TCollectionSubscription

### Directories
[framework](../INDEX.md) / [Collections](./INDEX.md) / **`TCollectionSubscription`**

## Class Info
**Location:** `framework/Collections/TCollectionSubscription.php`
**Namespace:** `Prado\Collections`

## Overview
TCollectionSubscription provides subscription to ArrayAccess collections without passing by reference. Unlike [TArraySubscription](./TArraySubscription.md) which works with PHP arrays, this class works only with ArrayAccess objects.

## Inheritance

Extends [TArraySubscription](./TArraySubscription.md) and provides the same functionality but without array reference passthrough.

## Usage

```php
$collection = new TMap();
$subscription = new TCollectionSubscription($collection, 'key', 'value');

$subscription->subscribe();
echo $collection['key'];  // 'value'

$subscription->unsubscribe();
echo $collection['key'];  // null
```

## Key Difference from TArraySubscription

- [TArraySubscription](./TArraySubscription.md) - Works with PHP arrays by reference
- `TCollectionSubscription` - Works with ArrayAccess objects without reference

## See Also

- [TArraySubscription](./TArraySubscription.md) - Parent class that works with PHP arrays
- [TPriorityPropertyTrait](./TPriorityPropertyTrait.md) - Used for priority support
