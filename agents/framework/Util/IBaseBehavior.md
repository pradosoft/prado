# Util/IBaseBehavior

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`IBaseBehavior`**

## Class Info
**Location:** `framework/Util/IBaseBehavior.php`
**Namespace:** `Prado\Util`
**Extends:** `IPriorityProperty`

## Overview
`IBaseBehavior` is the root interface for all PRADO behaviors. Both stateful per-instance behaviors ([`IBehavior`](IBehavior.md)) and stateless class-wide behaviors ([`IClassBehavior`](IClassBehavior.md)) derive from this interface.

Behaviors extend owner components at runtime with new methods, properties, and `dy*` dynamic event handlers — essentially run-time traits. A single `IClassBehavior` instance can attach to many owners simultaneously; an `IBehavior` instance attaches to exactly one owner.

## Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `CONFIG_KEY` | `'=config='` | Array key for behavior instance configuration data passed to `init()` |

## Interface Methods

| Method | Description |
|--------|-------------|
| `init($config)` | Processes behavior configuration (called before `attach()`). Handles XML/array configs from [`TBehaviorsModule`](TBehaviorsModule.md). |
| `attach(TComponent $component)` | Attaches the behavior to a component. |
| `detach(TComponent $component)` | Detaches the behavior from a component. |
| `getName(): ?string` | Returns the behavior's name as registered in the owner. |
| `setName(?string $value)` | Sets the behavior name. |
| `getEnabled(): bool` | Returns whether the behavior is enabled. |
| `setEnabled($value)` | Enables or disables the behavior. |
| `getOwners(): array` | Returns all owner components (one for `IBehavior`, many for `IClassBehavior`). |
| `hasOwner(): bool` | True if attached to at least one owner. |
| `isOwner(object $component): bool` | True if `$component` is an owner of this behavior. |
| `syncEventHandlers(?object $component = null, $attachOverride = 0)` | Syncs event handler attachment for a specific owner or all owners. |

## Dynamic Events (`dy*`)

The `dy*` event system allows owners to call behavior-implemented methods as optional AOP-style hooks. When a component calls `$this->dyFoo($arg)`, all attached enabled behaviors with a `dyFoo()` method are invoked in priority order via a [`TCallChain`](TCallChain.md). The first argument acts as the pass-through return value.

## See Also

- [`IBehavior`](IBehavior.md) — per-instance stateful behavior
- [`IClassBehavior`](IClassBehavior.md) — class-wide stateless behavior
- [`TBaseBehavior`](TBaseBehavior.md) — abstract base implementation
- [`TBehavior`](TBehavior.md) / [`TClassBehavior`](TClassBehavior.md) — concrete base classes
