# TNoUnserializeBehaviorTrait

### Directories

[Util](../) > [Behaviors](Behaviors/) > TNoUnserializeBehaviorTrait

**Location:** `framework/Util/Behaviors/TNoUnserializeBehaviorTrait.php`
**Namespace:** `Prado\Util\Behaviors`

## Overview

This trait prevents a behavior from persisting across serialization/unserialization. When the owner is unserialized (via `__wakeup` and `dyWakeUp`), this trait removes itself from the owner. This is useful for deprecating behaviors that should not survive serialization.

## Key Properties/Methods

- `dyWakeUp([TCallChain](../TCallChain.md) $chain)` - Dynamic event handler that removes the behavior from its owner upon unserialization

## See Also

- [TNoUnserializeClassBehaviorTrait](./TNoUnserializeClassBehaviorTrait.md)
