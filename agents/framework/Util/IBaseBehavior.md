# IBaseBehavior

### Directories

[Util](../) > IBaseBehavior

**Location:** `framework/Util/IBaseBehavior.php`
**Namespace:** `Prado\Util`

## Overview

Base interface for all PRADO behaviors. Defines the contract for behaviors that extend owner components with new properties, methods, and dynamic event handling.

## Extends

- `IPriorityProperty`

## Key Methods

| Method | Description |
|--------|-------------|
| `init($config)` | Processes behavior configuration from `[TBehaviorsModule](TBehaviorsModule.md)` |
| `attach([TComponent](../TComponent.md) $component)` | Attaches behavior to a component |
| `detach([TComponent](../TComponent.md) $component)` | Detaches behavior from a component |
| `getName(): ?string` | Returns the behavior name |
| `setName(?string $value)` | Sets the behavior name |
| `getEnabled(): bool` | Returns whether behavior is enabled |
| `setEnabled($value)` | Sets enabled status |
| `getOwners(): array` | Returns array of owner components |
| `hasOwner(): bool` | Returns true if attached to an owner |
| `isOwner(object $component): bool` | Checks if component is an owner |
| `syncEventHandlers(?object $component, $attachOverride)` | Syncs event handlers with owner |

## Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `CONFIG_KEY` | `'=config='` | Array key for behavior instance config |

## See Also

- `[IBehavior](IBehavior.md)` - Stateful per-instance behavior interface
- `[IClassBehavior](IClassBehavior.md)` - Stateless class-wide behavior interface
- `[TBaseBehavior](TBaseBehavior.md)` - Base implementation
