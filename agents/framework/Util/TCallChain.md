# Util/TCallChain

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TCallChain`**

## Class Info
**Location:** `framework/Util/TCallChain.php`
**Namespace:** `Prado\Util`
**Extends:** `[TList](../Collections/TList.md)`

## Overview
AOP-style call chain for `dy*` dynamic events. When a `[TComponent](../TComponent.md)` raises a `dy*` event, `[TComponent](../TComponent.md).__call()` creates a `TCallChain` populated with matching behavior methods, then calls the first one. Each behavior may call `$chain->dyMethodName(args)` to continue to the next handler or return early to short-circuit.

## How It Works

```
[TComponent](../TComponent.md)::someMethod():
    result = $this->dyValidate($value)
             ↓ via __call()
    [TComponent](../TComponent.md) builds TCallChain('dyValidate')
    adds [behavior1, 'dyValidate']
    adds [behavior2, 'dyValidate']
    calls $chain->call()

    → behavior1::dyValidate($value, $chain)
        optionally: return $chain->dyValidate($newValue)
        → behavior2::dyValidate($newValue, $chain)
            optionally: return $chain->dyValidate($newerValue)
            → (end of chain, returns last value)
```

## Key Methods

```php
$chain = new TCallChain('dyMethodName');
$chain->addCall($callable, $args);  // add a behavior+method pair
$chain->call($method, $args);       // invoke the chain (usually via magic)
```

The `__call()` on `TCallChain` (via `[IDynamicMethods](IDynamicMethods.md)`) catches any `dy*` method call and routes it to `call()`, enabling the fluent chain syntax.

## Parameter Passing Rules

When `$chain->dyFoo($newVal)` is called from within a behavior:
- The new arguments **replace** the original arguments positionally.
- Extra original arguments (beyond what was provided to `$chain->dyFoo()`) are appended.
- For `[IClassBehavior](IClassBehavior.md)`, the first original argument (the owner component) is **never replaced** — replacement starts from the second argument.
- `$chain` itself is **always appended** as the last argument for the next call.

## Usage in Behaviors

```php
// IBehavior ([TBehavior](TBehavior.md) subclass):
public function dyValidate($value, [TCallChain](TCallChain.md) $chain)
{
    if ($value < 0) {
        return 0;  // short-circuit: return without calling $chain
    }
    return $chain->dyValidate($value * 2);  // pass modified value down
}

// IClassBehavior ([TClassBehavior](TClassBehavior.md) subclass):
public function dyValidate($owner, $value, [TCallChain](TCallChain.md) $chain)
//                          ^^^^^^ always first for class behaviors
{
    return $chain->dyValidate($value);
}
```

## Patterns & Gotchas

- **Return value** — the final value returned from `raiseEvent()` is whatever the last-called behavior returns (or the initial return from the component if no chain handlers ran).
- **Short-circuit** — returning without calling `$chain` stops propagation. Subsequent behaviors don't run.
- **`TCallChain` extends `[TList](../Collections/TList.md)`** — all standard list operations are available, but the chain is consumed (single-pass iterator).
- **`[IDynamicMethods](IDynamicMethods.md)` magic** — `__call()` on `TCallChain` catches `dy*` calls; do not confuse with regular method dispatch.
- **Not reusable** — a `TCallChain` instance is built fresh for each `dy*` event raise.
