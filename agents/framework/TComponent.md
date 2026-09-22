# TComponent

### Directories
[framework](./INDEX.md) / **`TComponent`**

## Class Info
**Location:** `framework/TComponent.php`
**Namespace:** `Prado`

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

### Property Paths

A path is a sequence of names, each introduced by a `.` or `@` separator; the first name has an implied `.`. A `.` reads a property, an `@` reads a behavior via `asa()`.

| Method | Purpose |
|---|---|
| `getSubProperty($path)` | Reads the value at a path. An undefined property throws; an unattached `@` behavior yields `null`. |
| `setSubProperty($path, $value)` | Writes one path, through `TPropertyValue::applyProperty()`. A path ending in an `@` hop addresses a behavior, so the call is a no-op. |
| `setSubProperties($properties)` | Writes a `path => value` map, or any `\Traversable` of one such as a `TMap` of configuration attributes. |
| `sortPropertyPaths($properties)` (protected static) | Reorders a `path => value` map into the application order `setSubProperties()` uses. |

`setSubProperties()` applies the map in a pre-order walk of the path tree: a node's own properties precede its descendants, an `@` behavior subtree precedes the `.` subproperty subtree at the same node, and paths that diverge keep their declaration order. A parent path therefore always resolves before a path nested beneath it.

```php
// Both orders write 'child', because 'Cfg' is applied first either way.
$obj->setSubProperties(['Cfg.Size' => 'child', 'Cfg' => 'value']);
$obj->setSubProperties(['Cfg' => 'value', 'Cfg.Size' => 'child']);
```

Every source that applies a group of configured properties routes through this order. `setSubProperties()` is called by `Prado::createComponent()` for a component built from a `['class' => ...]` array, such as a behavior or a shell action; by `TApplication` for modules, services, application properties, and parameters; and by `TPageService::runPage()`, `TParameterModule`, `TDataSourceConfig`, `TLogRouter`, `TUrlMapping`, `THttpHeadersManager`, `TStreamNotificationCallback`, `TFeedService`, `TJsonService`, and `TSoapService` for their own configuration. `TTemplate::instantiateIn()`, `TTheme::applySkin()`, and `TControl::evaluateBoundProperties()` call `sortPropertyPaths()` directly, because each resolves a per-entry type or expression as it goes and has no plain map to hand over.

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

**Event parameter lifecycle** — if `$param` implements [`IEventCycleParameter`](./IEventCycleParameter.md) (or `isa(IEventCycleParameter::class)` for behavior-wrapped objects), `raiseEvent` automatically:
1. Calls `$param->setEventName($name)` (resets `ParameterChanged`).
2. Calls `$param->preRaiseEvent(...)` before any handlers run.
3. Calls `$param->postRaiseEvent(...)` after all handlers complete.

**Dynamic events dispatched by `raiseEvent`:**
- `dyPreRaiseEvent($name, $sender, $param, $responsetype, $postfunction)` — before handlers.
- `dyIntraRaiseEventTestHandler($handler, $sender, $param, $name)` — return `false` to skip a handler.
- `dyIntraRaiseEventPostHandler($name, $sender, $param, $handler, $response)` — after each handler.
- `dyPostRaiseEvent($responses, $name, $sender, $param, $responsetype, $postfunction)` — after all handlers.

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
$component->getBehaviors();                      // array<string, IBaseBehavior> of all behaviors
$component->getBehaviors(MyBehavior::class);     // array<string, MyBehavior> filtered by class (@template)
$component->getBehavior('myBehavior');
$component->asa('MyBehavior');       // returns behavior by class name (typed: asa(T::class): ?T)
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

## Other Notable Methods

- **`getClassHierarchy(bool $lowercase = false): string[]`** — returns class name + all parent class, trait, and interface names. Result is cached statically per class/lowercase combination; cache now correctly handles both lowercase and non-lowercase variants independently.
- **`evaluateStatements(string $statements): string`** — evals PHP code, captures output. Calls `ob_end_clean()` in both the success and exception paths, preventing output buffer leaks on eval errors.
- **`__isset($name)`** / **`__call`** — both check `isset($this->_e[...])` and `isset($this->_m)` before accessing them, guarding against uninitialized state during construction or deserialization.

## Patterns & Gotchas

- **`isa()` is not `instanceof`** — `isa()` returns true if the object IS the class OR has an attached behavior of that class. Use it for duck-typing with behaviors.
- **`isa()` narrows for static analysis** — `isa()` declares `@template T of object`, `@param class-string<T>|T $class` and `@phpstan-assert-if-true T $this`, so PHPStan narrows the subject inside the guard exactly as `instanceof` does. The tag covers intersection subjects such as `IService&TComponent`, which never reach [TComponentIsaTypeSpecifyingExtension](./PHPStan/TComponentIsaTypeSpecifyingExtension.md). Never add an inline `@var` after an `isa()` guard.
- **Apply a group of properties with `setSubProperties()`, never a `setSubProperty()` loop** — a loop writes in declaration order, so `Cfg="value"` declared after `Cfg.Size="child"` replaces the object the nested write reached and the nested value is lost. A source that cannot hand over a plain map, because it switches on a per-property type, orders its own map through `sortPropertyPaths()` first.
- **`dy*` must always accept `TCallChain` as last parameter** — even if the behavior doesn't continue the chain.
- **`_getZappableSleepProps()` must call parent** — accumulated across the entire class hierarchy.
- **`$_e` vs `$_ue`** — instance events use `$_e`, global events use static `$_ue`. Never access these directly; use the API.
- **Behavior event handlers** — behaviors should attach/detach their handlers in `attach()`/`detach()`, tracking via `eventsLog`.
