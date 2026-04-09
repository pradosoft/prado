# TNoUnserializeClassBehaviorTrait

### Directories

[Util](../) > [Behaviors](Behaviors/) > TNoUnserializeClassBehaviorTrait

**Location:** `framework/Util/Behaviors/TNoUnserializeClassBehaviorTrait.php`
**Namespace:** `Prado\Util\Behaviors`

## Overview

This trait prevents a class behavior from persisting across serialization/unserialization. When the owner is unserialized (via `__wakeup` and `dyWakeUp`), this trait removes itself from the owner. This is useful for deprecating class behaviors that should not survive serialization.

## Key Properties/Methods

- `dyWakeUp(object $owner, [TCallChain](../TCallChain.md) $chain)` - Dynamic event handler that removes the behavior from its owner upon unserialization

## See Also

- [TNoUnserializeBehaviorTrait](./TNoUnserializeBehaviorTrait.md)
