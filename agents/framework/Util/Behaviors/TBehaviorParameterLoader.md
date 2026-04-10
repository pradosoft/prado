# Util/Behaviors/TBehaviorParameterLoader

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Behaviors](./INDEX.md) / **`TBehaviorParameterLoader`**

## Class Info
**Location:** `framework/Util/Behaviors/TBehaviorParameterLoader.php`
**Namespace:** `Prado\Util\Behaviors`

## Overview
TBehaviorParameterLoader implements attaching Behaviors from Parameters before any work has been done. It allows behaviors to be loaded through application configuration parameters and can attach behaviors to specific classes, pages, modules, or the application itself.

## Key Properties/Methods

- `dyInit($config)` - Dynamic event handler called after a parameter class is loaded, attaches the specified behavior
- `attachTPageServiceHandler($sender, $param)` - Handler for attaching page behaviors at `onBeginRequest`
- `attachTPageBehaviors($sender, $page)` - Attaches behaviors to [TPage](../../Web/UI/TPage.md) during `OnPreInitPage` event
- `attachModuleBehaviors($sender, $page)` - Attaches behaviors to modules at `onInitComplete`
- `reset()` - Resets module and page behavior cache data
- `getBehaviorName()` / `setBehaviorName($name)` - Gets/sets the behavior name
- `getBehaviorClass()` / `setBehaviorClass($className)` - Gets/sets the behavior class
- `getAttachTo()` / `setAttachTo($attachto)` - Gets/sets attachment target (page, module:moduleid, application, or subproperty path)
- `getAttachToClass()` / `setAttachToClass($attachto)` - Gets/sets class to attach behavior to
- `getProperties()` - Gets additional properties for the behavior

## See Also

- [TBehaviorsModule](../TBehaviorsModule.md)
- [TParameterModule](../TParameterModule.md)
- [TParameterizeBehavior](./TParameterizeBehavior.md)
