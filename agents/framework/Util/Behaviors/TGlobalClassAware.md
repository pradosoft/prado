# Util/Behaviors/TGlobalClassAware

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Behaviors](./INDEX.md) / **`TGlobalClassAware`**

## Class Info
**Location:** `framework/Util/Behaviors/TGlobalClassAware.php`
**Namespace:** `Prado\Util\Behaviors`

## Overview
This behavior registers the `fxAttachClassBehavior` and `fxDetachClassBehavior` handlers of the owner to listen for dynamic changes to its class behaviors after instantiation. Without this behavior (or `listen()`), an instanced [TComponent](../../TComponent.md) will not update its class behaviors when there is a change in the global class behaviors.

## Key Properties/Methods

- `attachEventHandlers([TComponent](../../TComponent.md) $component)` - Attaches handlers for `fxAttachClassBehavior` and `fxDetachClassBehavior` dynamic events
- `detachEventHandlers([TComponent](../../TComponent.md) $component)` - Detaches the handlers on behavior detachment

## See Also

- [TComponent](../../TComponent.md)
- [TClassBehavior](../TClassBehavior.md)
