# Util/Behaviors/TTimeZoneParameterBehavior

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Behaviors](./INDEX.md) / **`TTimeZoneParameterBehavior`**

## Class Info
**Location:** `framework/Util/Behaviors/TTimeZoneParameterBehavior.php`
**Namespace:** `Prado\Util\Behaviors`

## Overview
TTimeZoneParameterBehavior sets PHP's default timezone from an application parameter. It can be attached to [TApplication](../../TApplication.md) or any [TComponent](../../TComponent.md). The behavior routes changes to the TimeZoneParameter to `date_default_timezone_set()`. If no application parameter is set, the TimeZone property is used as a fallback.

## Key Properties/Methods

- `attach($owner)` - Sets timezone from application parameter and attaches route behavior
- `detach($owner)` - Removes the parameter route behavior
- `setEnabled($enabled)` - Enables/disables the parameter route behavior
- `getTimeZoneParameter()` / `setTimeZoneParameter($value)` - Gets/sets the application parameter key (default: 'prop:TimeZone')
- `getTimeZone()` - Gets current timezone from `date_default_timezone_get()`
- `setTimeZone($value)` - Sets timezone via `date_default_timezone_set()`

## See Also

- [TParameterizeBehavior](./TParameterizeBehavior.md)
- [TMapRouteBehavior](./TMapRouteBehavior.md)
