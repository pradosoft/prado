# TBaseBehavior

### Directories

[Util](../) > TBaseBehavior

**Location:** `framework/Util/TBaseBehavior.php`
**Namespace:** `Prado\Util`

## Overview

TBaseBehavior is the abstract base class for both TBehavior (per-instance) and TClassBehavior (class-wide) implementations. It provides event handler management, enable/disable functionality, and serialization support.

## Key Properties/Methods

- `getName()` / `setName($value)` - The behavior's name in the owner
- `getEnabled()` / `setEnabled($value)` - Enable/disable the behavior
- `getRetainDisabledHandlers()` / `setRetainDisabledHandlers($value)` - Control handler retention when disabled
- `events()` - Declare events and their handlers (override in subclasses)
- `eventsLog()` - Get cached event handlers
- `attach($component)` - Attach behavior to a component
- `detach($component)` - Detach behavior from a component
- `syncEventHandlers($component, $attachOverride)` - Synchronize handlers with owner
- `mergeHandlers(...$args)` - Static method to merge event handler arrays
- `getStrictEvents()` - Whether to strictly enforce event existence (default true)

## See Also

- [TBehavior](TBehavior.md)
- [TClassBehavior](TClassBehavior.md)
- [IBaseBehavior](IBaseBehavior.md)
