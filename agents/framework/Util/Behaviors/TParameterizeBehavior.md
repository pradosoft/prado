# Util/Behaviors/TParameterizeBehavior

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Behaviors](./INDEX.md) / **`TParameterizeBehavior`**

## Class Info
**Location:** `framework/Util/Behaviors/TParameterizeBehavior.php`
**Namespace:** `Prado\Util\Behaviors`

## Overview
TParameterizeBehavior sets a specific property on the owner object to an application parameter value. It can optionally route changes to the application parameter to the property in real-time using a TMapRouteBehavior. Supports localization of parameter values and default values when parameters are not set.

## Key Properties/Methods

- `attach($owner)` - Sets the owner property to the parameter value, attaches route behavior if enabled
- `detach($owner)` - Removes the parameter route behavior
- `setEnabled($enabled)` - Enables/disables the parameter route behavior
- `getParameter()` / `setParameter($value)` - Gets/sets the application parameter key
- `getProperty()` / `setProperty($value)` - Gets/sets the owner property name
- `getDefaultValue()` / `setDefaultValue($value)` - Gets/sets default value when parameter is null
- `getLocalize()` / `setLocalize($value)` - Whether to localize the parameter/default value
- `getRouteBehaviorName()` / `setRouteBehaviorName($value)` - Name for the internal TMapRouteBehavior
- `getValidNullValue()` / `setValidNullValue($value)` - Whether to set null parameter values on the property

## See Also

- [TBehaviorParameterLoader](./TBehaviorParameterLoader.md)
- [TMapRouteBehavior](./TMapRouteBehavior.md)
