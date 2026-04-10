# Util/TClassBehavior

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TClassBehavior`**

## Class Info
**Location:** `framework/Util/TClassBehavior.php`
**Namespace:** `Prado\Util`
**Extends:** `[TBaseBehavior](TBaseBehavior.md)`

## Overview
Class-wide behavior — one `TClassBehavior` instance can be attached to **many** owners simultaneously. Designed to be **stateless** (no per-object data). When `[TComponent](../TComponent.md)` calls a method on a class behavior, it automatically injects the calling owner as the **first parameter** so the behavior knows which object is involved.

Contrast with `[TBehavior](TBehavior.md)` (stateful, one owner).

## Multiple Owners

Owners are tracked in a `[TWeakList](../Collections/TWeakList.md)` (`_owners`), preventing strong references that would block GC. Handler-installation state is tracked per-owner in a `WeakMap` (PHP 8+) or array (PHP 7 fallback).

## TComponent's Role

`[TComponent](../TComponent.md)` calls class behavior methods differently:
```php
// Owner calls:
$owner->myBehaviorMethod('arg1', 'arg2');

// [TComponent](../TComponent.md) dispatches to:
$behavior->myBehaviorMethod($owner, 'arg1', 'arg2');
//                           ^^^^^^ injected automatically
```

So all class behavior methods **must** accept the owner as their first argument (before `[TCallChain](TCallChain.md)`).

## Key Methods

| Method | Purpose |
|--------|---------|
| `attach($component)` | Adds component to `_owners` list, syncs event handlers. |
| `detach($component)` | Removes component from `_owners`, detaches handlers for that component. |
| `getOwners()` | Returns `[TWeakList](../Collections/TWeakList.md)` of all current owners. |
| `hasOwner()` | `true` if any owner is attached. |
| `isOwner($component)` | `true` if `$component` is in the owners list. |
| `dyEnableBehaviors($owner, ?[TCallChain](TCallChain.md))` | Sync handlers for `$owner` when behaviors enabled. |
| `dyDisableBehaviors($owner, ?[TCallChain](TCallChain.md))` | Sync handlers for `$owner` when behaviors disabled. |

## Implementing a Class Behavior

```php
class AuditBehavior extends [TClassBehavior](TClassBehavior.md)
{
    // Note: first parameter is always the owner
    public function dyBeforeSave($owner, $chain)
    {
        [Prado](../Prado.md)::log("Saving: " . get_class($owner), [TLogger](TLogger.md)::INFO);
        return $chain->dyBeforeSave($owner);  // continue chain
    }

    // Attach/detach handlers per owner:
    public function attach($component)
    {
        parent::attach($component);
        $component->attachEventHandler('OnSave', [$this, 'handleSave']);
    }
    public function detach($component)
    {
        $component->detachEventHandler('OnSave', [$this, 'handleSave']);
        parent::detach($component);
    }

    // Event handler also receives the owner as context:
    public function handleSave($sender, $param)
    {
        // $sender is the owner
    }
}

// Register for all instances of MyRecord:
[TComponent](../TComponent.md)::attachClassBehavior('audit', new AuditBehavior(), MyRecord::class);
```

## Cloning

`__clone()` resets `_owners = null` and `_handlersInstalled = null`. The cloned behavior starts with no owners.

## Patterns & Gotchas

- **Stateless** — never store per-object state in properties; every owner shares the same behavior instance. Use `$owner` parameter for per-instance decisions.
- **First parameter injection** — always remember the first parameter in every method is the owner object when called from a class behavior context.
- **Attaching to `[TComponent](../TComponent.md)` is forbidden** — must be attached to a concrete subclass.
- **`[TBehavior](TBehavior.md)` vs `TClassBehavior`** — if you need to store per-instance data, use `[TBehavior](TBehavior.md)`. If behavior is truly stateless and applies to many objects, use `TClassBehavior`.
