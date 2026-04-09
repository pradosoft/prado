# TComponent

**Location:** `framework/TComponent.php`
**Namespace:** `Prado`
**Size:** ~89KB — the most complex single file in the framework.

## Overview

Base class for nearly all Prado objects. Implements three orthogonal systems: **properties** (getter/setter via magic), **events** (listener lists, three prefixes), and **behaviors** (mixins with AOP-style interception). Also handles serialization, cloning, and weak-reference lifetime management.

## Property System

Properties are defined by public `getXxx()`/`setXxx()` pairs. Magic `__get`/`__set`/`__isset`/`__unset` dispatch to these. Read-only = has getter only. Write-only = has setter only.

```php
// In subclass:
public function getName(): string { return $this->_name; }
public function setName(string $v): void { $this->_name = $v; }

// Usage (via magic):
$obj->Name = 'foo';
echo $obj->Name;
```

- `getProperty($name)` / `setProperty($name, $value)` — programmatic access by string name.
- `hasProperty($name)` — checks if getter or setter exists.
- `canGetProperty($name)` / `canSetProperty($name)` — checks read/write capability.
- `js*` prefix — alternate getters for JavaScript-friendly output (same property, different format).
- Dot-path properties: `Parent.Page.Title` evaluated recursively via `__get`.

## Event System — Three Prefixes

### `on*` — Object Events (listener lists)
Raised with `$this->raiseEvent('OnEventName', $this, $param)`. Handlers stored per-instance in `$_e`.

```php
$component->attachEventHandler('OnClick', [$this, 'handleClick']);
$component->detachEventHandler('OnClick', [$this, 'handleClick']);
```

`raiseEvent()` options:
- `TComponent::RAISE_EVENT_BROADCAST` — raise even if no handlers.
- `TComponent::RAISE_EVENT_GLOBAL` — propagate as `fx*` global event too.

### `fx*` — Global Events (static, application-wide)
Handlers stored in static `$_ue` per event name. Any listening object receives them.

```php
// Listen:
$this->listen();          // auto-registers all public fxXxx methods
$this->unlisten();        // removes them

// Check auto-listen:
public function getAutoGlobalListen(): bool { return true; }
```

### `dy*` — Dynamic Events (behavior dispatch)
Called on the owner; dispatched to attached behaviors that implement the method. Returns value from TCallChain. Used for AOP-style interception.

```php
// In component code:
$result = $this->dyValidate($value, $chain);  // dispatched via __call

// In behavior:
public function dyValidate($value, TCallChain $chain) {
    // validate, modify $value...
    return $chain->dyValidate($newValue); // continue chain
}
```

## Behavior System

Behaviors are mixins attached to a component instance or to an entire class.

### Instance Behaviors

```php
$component->attachBehavior('myBehavior', new MyBehavior());
$component->detachBehavior('myBehavior');
$component->enableBehavior('myBehavior');
$component->disableBehavior('myBehavior');
$component->getBehaviors();          // TPriorityMap of all behaviors
$component->getBehavior('myBehavior');
$component->asa('MyBehavior');       // returns behavior by class name
$component->isa('MyBehavior');       // true if class is or has behavior of that class
```

### Class Behaviors
```php
TComponent::attachClassBehavior('sharedBehavior', new MyClassBehavior(), MyClass::class);
TComponent::detachClassBehavior('sharedBehavior', MyClass::class);
```
All existing + future instances of `MyClass` receive the behavior. Cannot attach to `TComponent` itself.

### IBehavior vs IClassBehavior
- `IBehavior` / `TBehavior` — one owner, stateful. Owner stored as `WeakReference`.
- `IClassBehavior` / `TClassBehavior` — many owners, stateless. `TComponent` injects the owner as the first parameter to all method calls.

## Lifecycle / Serialization

- `__clone()` — re-attaches behaviors; fires `dyClone` dynamic event. Always call `parent::__clone()`.
- `__sleep()` / `__wakeup()` — `_getZappableSleepProps()` returns an array of private property names (mangled format: `"\0ClassName\0_prop"`) that should be excluded from serialization. Every class adds its own zappable props by overriding and calling `parent::_getZappableSleepProps($exprops)`.
- `__destruct()` — removes all global event (`fx*`) listeners; detaches behaviors.

## Key Static Methods / Constants

```php
TComponent::RAISE_EVENT_BROADCAST   // flag: raise even without handlers
TComponent::RAISE_EVENT_GLOBAL      // flag: also raise as global fx event
```

## Patterns & Gotchas

- **`isa()` is not `instanceof`** — `isa()` returns true if the object IS the class OR has an attached behavior of that class. Use it for duck-typing with behaviors.
- **`dy*` must always accept `TCallChain` as last parameter** — even if the behavior doesn't continue the chain.
- **`_getZappableSleepProps()` must call parent** — accumulated across the entire class hierarchy.
- **`$_e` vs `$_ue`** — instance events use `$_e`, global events use static `$_ue`. Never access these directly; use the API.
- **Behavior event handlers** — behaviors should attach/detach their handlers in `attach()`/`detach()`, tracking via `eventsLog`.
