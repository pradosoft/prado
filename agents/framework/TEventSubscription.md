# TEventSubscription

**Location:** `framework/TEventSubscription.php`
**Namespace:** `Prado`

## Overview

`TEventSubscription` extends `TCollectionSubscription` (which extends `TArraySubscription`) to provide RAII-style event handler attachment. Attach an event handler to a `TComponent` event for a limited lifetime; when the subscription is destructed, the handler is automatically detached.

The component is held via `WeakReference` so the subscription does not prevent garbage collection.

See also: `TArraySubscription` in `framework/Collections/TArraySubscription.php` for the base collection subscription pattern.

## Constructor

```php
new TEventSubscription(
    ?TComponent $component = null,  // component owning the event
    mixed $event = null,            // event name string (case-insensitive)
    mixed $handler = null,          // callable or [object, method]
    null|int|float $priority = null,
    ?bool $autoSubscribe = null,    // null = auto-subscribe if component + event set
    mixed $index = null
)
```

For `fx*` global events, `$component` may be `null` (defaults to `Prado::getApplication()`).

## Key Methods

```php
$sub->getComponent(): ?TComponent           // resolves WeakReference
$sub->setComponent(?TComponent $v): static  // cannot change while subscribed
$sub->getEvent(): ?string                   // event name (stored lowercase)
$sub->setEvent(mixed $v): static            // cannot change while subscribed

$sub->subscribe(): ?bool    // attaches handler; false if already subscribed
$sub->unsubscribe(): ?bool  // detaches handler
$sub->getIsSubscribed(): bool
```

`setArray()` is blocked — throws `TInvalidOperationException` if called with non-null.

## Usage

```php
// Temporary event handler for one request:
$sub = new TEventSubscription(
    $dispatcher,
    'fxSignalInterrupt',
    function($sender, $param) use (&$exit) { $exit = true; }
);
// ... handler is active ...
unset($sub); // or $sub goes out of scope → handler detached

// Named event on a control:
$sub = new TEventSubscription($button, 'onClick', [$this, 'handleClick']);
// handler active while $sub lives

// Global (fx*) event — component defaults to application:
$sub = new TEventSubscription(null, 'fxSomeGlobalEvent', $handler);
```

## Patterns & Gotchas

- **WeakReference for component** — the component is NOT kept alive by the subscription. If the component is GC'd, `getComponent()` returns null and `unsubscribe()` becomes a no-op.
- **Event name is lowercased** — `setEvent()` stores the name in lowercase for consistent lookup.
- **Auto-subscribe** — when both `$component` and `$event` are provided in the constructor and `$autoSubscribe` is null, the handler is automatically attached.
- **setArray() restriction** — `TEventSubscription` resolves the event handler list lazily from the component; direct `setArray()` is forbidden.
- **Priority** — maps to the event's `TPriorityList` handler priority (lower = higher precedence).
