# Util/TBehavior

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TBehavior`**

## Class Info
**Location:** `framework/Util/TBehavior.php`
**Namespace:** `Prado\Util`
**Extends:** `[TBaseBehavior](TBaseBehavior.md)`

## Overview
Per-instance behavior. One `TBehavior` instance has exactly **one** owner component. Use for stateful behaviors that need to track per-object data. Contrast with `[TClassBehavior](TClassBehavior.md)` (stateless, many owners).

Owner is stored as a `WeakReference` — the behavior does not prevent the owner from being garbage-collected.

## Key Methods

| Method | Purpose |
|--------|---------|
| `attach($owner)` | Registers owner (WeakRef), calls `parent::attach()` which syncs event handlers. Throws if already attached. |
| `detach($owner)` | Detaches handlers from owner, clears `_owner` and `_name`. Throws if wrong owner. |
| `getOwner()` | Returns the owning `[TComponent](../TComponent.md)` (derefs WeakRef) or `null`. |
| `getOwners()` | Returns single-element array `[$owner]` for API compatibility with `[IClassBehavior](IClassBehavior.md)`. |
| `hasOwner()` | `true` when `_owner !== null`. |
| `isOwner($component)` | `true` when `$component` is this behavior's owner. |
| `dyEnableBehaviors(?[TCallChain](TCallChain.md))` | Fires when owner's behaviors are enabled; calls `syncEventHandlers()`. |
| `dyDisableBehaviors(?[TCallChain](TCallChain.md))` | Fires when owner's behaviors are disabled; calls `syncEventHandlers()`. |

## Implementing a Behavior

```php
class MyBehavior extends TBehavior
{
    // Property on the behavior acts as property on the owner:
    private string $_extra = '';
    public function getExtra(): string { return $this->_extra; }
    public function setExtra(string $v): void { $this->_extra = $v; }

    // Intercept a dy* event on the owner:
    public function dyValidate($value, [TCallChain](TCallChain.md) $chain)
    {
        if ($value === '') {
            return false;  // short-circuit — don't call $chain
        }
        return $chain->dyValidate($value);  // continue chain
    }

    // Attach/detach event handlers:
    public function attach($owner)
    {
        parent::attach($owner);
        $owner->attachEventHandler('OnSave', [$this, 'handleSave']);
    }
    public function detach($owner)
    {
        $owner->detachEventHandler('OnSave', [$this, 'handleSave']);
        parent::detach($owner);
    }
}
```

## Cloning

`__clone()` resets `_owner = null` and `_handlersInstalled = false`. When `[TComponent](../TComponent.md).__clone()` runs, it re-attaches each cloned behavior to the new instance.

## Serialization

`_getZappableSleepProps()` excludes `_owner` and `_handlersInstalled` from serialization (they're transient). Call `parent::_getZappableSleepProps($exprops)` first in subclasses.

## Patterns & Gotchas

- **Never store the owner directly** — the WeakReference prevents circular reference loops. Access it via `getOwner()`.
- **Always call `parent::attach()` / `parent::detach()`** — the parent syncs event handler registration.
- **Handlers registered in `attach()` must be removed in `detach()`** — use `eventsLog` (from `[TBaseBehavior](TBaseBehavior.md)`) to track auto-registered handlers if you use the `Events` property approach.
- **`_name` is cleared on `detach()`** — a behavior's `Name` property becomes `null` after detachment.
