# Util/IDynamicMethods

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`IDynamicMethods`**

## Class Info
**Location:** `framework/Util/IDynamicMethods.php`
**Namespace:** `Prado\Util`

## Overview
`IDynamicMethods` marks an object as capable of receiving undefined `dy*` dynamic events and `fx*` global events via the catch-all `__dycall($method, $args)` method.

[`TComponent`](../TComponent.md) checks for this interface when dispatching `dy*` calls. If an attached behavior implements `IDynamicMethods`, any `dy*` method call that is not explicitly declared on the behavior is routed to `__dycall()` instead of being silently ignored.

[`TCallChain`](TCallChain.md) implements this interface so that `$chain->dyFoo(...)` syntax works — `__dycall` catches the call and routes it to `TCallChain::call()`.

## Interface Method

```php
public function __dycall(string $method, array $args): mixed;
```

`$method` is the name of the dynamic method called (e.g., `'dyValidate'`). `$args` is the full argument list.

## Usage in TCallChain

```php
// When a behavior calls $chain->dyFoo($newVal),
// TCallChain::__dycall('dyFoo', [$newVal]) is invoked,
// which forwards to TCallChain::call($newVal).
```

## See Also

- [`TCallChain`](TCallChain.md) — implements this interface to enable the `$chain->dyFoo()` syntax
- [`TComponent`](../TComponent.md) — uses this interface when dispatching `dy*` events to behaviors
- [`IBaseBehavior`](IBaseBehavior.md) — behavior interface
