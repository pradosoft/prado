# Util/TBaseBehavior

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TBaseBehavior`**

## Class Info
**Location:** `framework/Util/TBaseBehavior.php`
**Namespace:** `Prado\Util`
**Extends:** `TApplicationComponent`
**Implements:** [`IBaseBehavior`](IBaseBehavior.md)
**Since:** 4.3.0

## Overview
`TBaseBehavior` is the abstract base class shared by both [`TBehavior`](TBehavior.md) (per-instance) and [`TClassBehavior`](TClassBehavior.md) (class-wide). It provides:

- `Name` and `Enabled` properties
- Event handler registration / de-registration via `events()` and `syncEventHandlers()`
- `RetainDisabledHandlers` control — whether to keep event handlers in the owner's event list when the behavior is disabled
- `eventsLog()` — caches `Closure` handlers across repeated attach/detach cycles and multiple owners
- Serialization via `__clone()`

Uses `TPriorityPropertyTrait` so that priority is tracked when inserted into an owner's priority map of behaviors.

## Key Properties

| Property | Default | Description |
|----------|---------|-------------|
| `Name` | `null` | Behavior name as registered in the owner |
| `Enabled` | `true` | When false, dynamic event handlers are not called |
| `RetainDisabledHandlers` | `false` | `true` = keep handlers attached when disabled; `null` = force detach; `false` = default logic |
| `StrictEvents` | `true` | Whether attaching event handlers to non-existent events throws an error |

## Declaring Event Handlers

```php
class MyBehavior extends TBehavior
{
    public function events(): array
    {
        return [
            'OnSomeEvent' => 'handleSomeEvent',
        ];
    }

    public function handleSomeEvent($sender, $param): void
    {
        // react to the event
    }
}
```

Handlers declared in `events()` are automatically attached on `attach()` and detached on `detach()`.

## Key Methods

| Method | Description |
|--------|-------------|
| `init($config)` | Stub; override for complex behavior config |
| `events(): array` | Override to declare `['EventName' => 'handlerMethodName']` pairs |
| `eventsLog(): ?array` | Returns cached event handler callables (Closures are cached across re-attaches) |
| `syncEventHandlers(?object $component, $attachOverride)` | Attaches/detaches events based on enabled state and `RetainDisabledHandlers` |
| `static mergeHandlers(...$args): array` | Merges multiple `events()` arrays from parent classes |
| `getAutoGlobalListen(): bool` | Returns `false` — behaviors do not auto-listen to global `fx*` events |

## RetainDisabledHandlers Semantics

| Value | Meaning |
|-------|---------|
| `false` | Default: attach when enabled+owner-behaviors-enabled, detach otherwise |
| `true` | Always keep handlers attached (even when behavior is disabled) |
| `null` | Always detach handlers (useful for behaviors that manage their own registration) |

## Cloning

`__clone()` resets `_name = null`, clearing ownership metadata. The cloned behavior starts detached.

## See Also

- [`TBehavior`](TBehavior.md) — per-instance stateful behavior
- [`TClassBehavior`](TClassBehavior.md) — class-wide stateless behavior
- [`IBaseBehavior`](IBaseBehavior.md) — interface contract
