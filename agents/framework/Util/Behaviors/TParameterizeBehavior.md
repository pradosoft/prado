# Util/Behaviors/TParameterizeBehavior

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Behaviors](./INDEX.md) / **`TParameterizeBehavior`**

## Class Info
**Location:** `framework/Util/Behaviors/TParameterizeBehavior.php`
**Namespace:** `Prado\Util\Behaviors`
**Extends:** [`TBehavior`](../TBehavior.md)

## Overview
`TParameterizeBehavior` sets a specific property on its owner object to the value of an application parameter. Optionally, it can install a [`TMapRouteBehavior`](TMapRouteBehavior.md) on the application parameter map so that runtime changes to the parameter are automatically forwarded to the property.

Supports localization of values (`Localize=true`) and a `DefaultValue` for when the parameter is not set.

## Configuration Examples

```xml
<!-- Set the page theme from an application parameter -->
<behavior name="PageTheme" Class="Prado\Util\Behaviors\TParameterizeBehavior"
          AttachTo="Page" Parameter="ThemeName" Property="Theme" DefaultValue="Basic" />

<!-- Localize the page title from a parameter -->
<behavior name="PageTitle" Class="Prado\Util\Behaviors\TParameterizeBehavior"
          AttachTo="Page" Parameter="TPageTitle" Property="Title" Localize="true" />

<!-- Route runtime parameter changes to module property -->
<behavior name="AuthExpireParam" Class="Prado\Util\Behaviors\TParameterizeBehavior"
          AttachTo="module:auth" Parameter="prop:TAuthManager.AuthExpire"
          Property="AuthExpire" RouteBehaviorName="TAuthManagerAuthExpireRouter" />

<!-- Class-wide: apply to all TSecurityManager instances -->
<behavior name="SecurityValidationKey" Class="Prado\Util\Behaviors\TParameterizeBehavior"
          AttachToClass="TSecurityManager"
          Parameter="prop:TSecurityManager.ValidationKey" Property="ValidationKey" />
```

## Key Properties

| Property | Description |
|----------|-------------|
| `Parameter` | Application parameter key to read the value from |
| `Property` | Owner property name to set |
| `DefaultValue` | Value to use when the parameter is not set or null |
| `ValidNullValue` | Whether to set the property when the parameter value is null (default: false) |
| `Localize` | Whether to pass the value through `Prado::localize()` before setting (default: false) |
| `RouteBehaviorName` | When set, installs a [`TMapRouteBehavior`](TMapRouteBehavior.md) on the parameter map under this name so runtime changes propagate to the property |

## Key Methods

- `attach($owner)` — reads the parameter, applies localization and defaulting, sets the property; installs route behavior if `RouteBehaviorName` is set
- `detach($owner)` — removes the route behavior from the parameter map
- `setEnabled($enabled)` — when toggled, enables/disables the route behavior

## Patterns & Gotchas

- **`Parameter` and `Property` are required** — `attach()` throws `TConfigurationException` if either is missing, or if the property is read-only or does not exist on the owner.
- **`RouteBehaviorName` is optional but recommended for dynamic configs** — without it, only the initial value is applied at attach time; subsequent parameter changes are not propagated.
- **`prop:ClassName.Property` convention** — allows using a dot-notation key into the parameter map that maps to a specific class property name (used for security keys and similar module-level parameters).
- **Class behaviors** — when attached via `AttachToClass`, the same behavior instance applies to every instance of that class; avoid storing per-object state.

## See Also

- [`TMapRouteBehavior`](TMapRouteBehavior.md)
- [`TMapLazyLoadBehavior`](TMapLazyLoadBehavior.md)
- [`TDbParameterModule`](../TDbParameterModule.md)
