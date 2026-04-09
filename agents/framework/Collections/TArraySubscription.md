# TArraySubscription / TCollectionSubscription

### Directories
[./](../INDEX.md) > [Collections](./INDEX.md) > [TArraySubscription](./TArraySubscription.md)

**Location:** `framework/Collections/TArraySubscription.php`
**Namespace:** `Prado\Collections`

## Overview

RAII-style subscribe/unsubscribe pattern for inserting an item into an array or collection for a limited scope. When the `TArraySubscription` instance goes out of scope (is destructed), the item is automatically removed from the collection.

Supports PHP arrays, `ArrayAccess` objects, [TList](./TList.md) / [TMap](./TMap.md) subclasses, and [IPriorityCollection](./IPriorityCollection.md) instances. For associative arrays, the original value at the key is saved and restored on unsubscribe. [IWeakCollection](./IWeakCollection.md) objects are handled transparently.

`TCollectionSubscription` is the base class; `TArraySubscription` is the primary public API.

## Key Features

- **Auto-unsubscribe on destruction** — destructor calls `unsubscribe()`.
- **Restore original value** — for associative / map arrays, the replaced value is saved and restored.
- **Priority support** — for `IPriorityCollection`, a priority can be specified.
- **List vs associative** — `isAssociative: false` uses `array_splice` for ordered-list insertion; `true` uses key-based access.

## Constructor

```php
new TArraySubscription(
    mixed &$array = null,
    mixed $key = null,
    mixed $item = null,
    null|int|float $priority = null,
    null|bool|int $isAssociative = 1,   // 1=true (default); false=list splice; null=auto-detect
    ?bool $autoSubscribe = null          // null=auto (subscribe if key or item set)
)
```

## Key Methods

```php
$sub->subscribe(): ?bool      // insert item; false if already subscribed, null if no array
$sub->unsubscribe(): ?bool    // remove item; false if not subscribed, null if array gone

$sub->getIsSubscribed(): bool
$sub->getArray(): array|ArrayAccess|null   // by reference; use carefully
$sub->setArray(array|ArrayAccess &$value): static  // cannot change while subscribed
$sub->getKey(): null|int|string     // discovered from collection if not set
$sub->setKey(mixed $value): static
$sub->getItem(): mixed
$sub->setItem(mixed $value): static
$sub->getPriority(): ?float
$sub->setPriority($value): static
$sub->getIsAssociative(): null|bool|int
$sub->setIsAssociative(null|bool|int $value): static
```

All setters throw `TInvalidOperationException` if called while subscribed.

## Usage Examples

```php
// Subscribe to a map (restores original value on unsubscribe):
$map = new TPriorityMap(['key' => 'original']);
$sub = new TArraySubscription($map, 'key', 'override', priority: 5);
// $map['key'] === 'override'
$sub->unsubscribe();
// $map['key'] === 'original'

// Subscribe to a list (auto-unsubscribes when $sub leaves scope):
{
    $list = [];
    $sub = new TArraySubscription($list, null, 'item', isAssociative: false);
    // $list === ['item']
} // $sub destructed → $list === []

// Subscribe to a TComponent event handler list:
$subscription = new TEventSubscription($component, 'onClick', $handler);
// handler is attached; unsubscribes when $subscription is destroyed
```

## TEventSubscription

`TEventSubscription` extends `TCollectionSubscription` for attaching event handlers to [TComponent](../TComponent.md) events. It stores the component as a `WeakReference`.

```php
new TEventSubscription(
    ?TComponent $component = null,
    mixed $event = null,       // event name (e.g. 'onClick', 'fxSomeEvent')
    mixed $handler = null,
    null|int|float $priority = null,
    ?bool $autoSubscribe = null,
    mixed $index = null
)
```

- For `fx*` global events, `$component` defaults to `Prado::getApplication()`.
- `setArray()` cannot be called directly on `TEventSubscription` (throws exception).
- The handler priority uses the event's [TPriorityList](./TPriorityList.md) ordering.

## Patterns & Gotchas

- **setArray() is by-reference** — PHP arrays are stored by reference; objects are stored as `WeakReference`. Do not assign the return of `getArray()` to a variable without `= &`.
- **Cannot change properties while subscribed** — all setters throw if `getIsSubscribed() === true`.
- **Auto-subscribe logic** — `autoSubscribe: null` subscribes automatically if `$key !== null || $item !== null`. Pass `autoSubscribe: false` to prevent auto-subscription when constructing with values.
- **`ICollectionFilter`** — if the collection implements `ICollectionFilter`, items are filtered through `filterItemForInput`/`filterItemForOutput` automatically (e.g., [TWeakList](./TWeakList.md) wraps/unwraps `WeakReference`).
- **List mode (`isAssociative: false`)** — uses `array_splice($array, $key, 0, [$item])` for insertion, which preserves index ordering. The stored key is reset to `null` after insertion because the index may shift.
