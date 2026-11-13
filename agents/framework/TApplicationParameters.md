# TApplicationParameters

### Directories
[framework](./INDEX.md) / **`TApplicationParameters`**

## Class Info
**Location:** `framework/TApplicationParameters.php`
**Namespace:** `Prado`
**Extends:** [`TMap`](Collections/TMap.md)
**Since:** 4.4.0

## Overview
The map of application parameters returned by `TApplication::getParameters()` (created by `TApplication::newParameters()`). A finalized key keeps its value for the rest of the request (issue #296). A parameter in `application.xml` marked final cannot be overridden by folder `config.xml` files, service configurations, includes, modules, or parameter loaders (`TParameterModule`, `TDbParameterModule`).

## Configuration

```xml
<parameters>
    <parameter id="DeveloperMode" value="0" final="true" />
</parameters>
```

```php
'parameters' => [
    'DeveloperMode' => ['value' => '0', 'final' => true],
],
```

`TApplicationConfiguration` strips `final` from the parameter and records it in `getFinalParameters()`. `TApplication::applyConfiguration()` adds the value, then finalizes it through `finalizeParameter()`.

## API

| Method | Result |
|---|---|
| `finalize($key)` | marks the key final; an absent key stays absent |
| `getIsFinal($key)` | whether the key is final |
| `getFinalKeys()` | final keys in finalization order |

## Operations on a final key

| Operation | Result |
|---|---|
| `itemAt()`, `$p[$key]` | value; null for an absent key, without `dyNoItem` |
| `contains()` | whether a value is stored |
| `add()`, `$p[$key] = $v`, `mergeWith()`, `$p[] = $v` onto a final next integer key | throws `TInvalidOperationException` (`appparameters_final`) |
| `remove()`, `unset($p[$key])`, `removeItem()`, `clear()`, `copyFrom()` | throws `appparameters_final` |
| `finalize()` again | no change |

## Behaviors on the map

| Behavior | Interaction with a final key |
|---|---|
| [`TMapRouteBehavior`](Util/Behaviors/TMapRouteBehavior.md) | indexed by key (see below); rejected changes call no route |
| [`TMapLazyLoadBehavior`](Util/Behaviors/TMapLazyLoadBehavior.md) | a final absent key raises no `dyNoItem`; the loader is not called |
| [`TParameterizeBehavior`](Util/Behaviors/TParameterizeBehavior.md) | the property holds a copy of the final value; code may still set it |
| [`TDbParameterModule`](Util/TDbParameterModule.md) | skips final rows on load, `get()` does not set them, `set()` throws before writing |

## Route index

`TApplicationParameters` implements [`IMapRouteIndex`](Util/Behaviors/TMapRouteBehavior.md#key-index). Each `TMapRouteBehavior` registers by its Parameter on attach. `add()` and `remove()` call the routes for the changed key, then the all-key routes, after the `dy` chain; indexed routes are left out of that chain (`getBehaviorsWithMethod()` override, cached until `flushBehaviorMethodCache()`). A write costs about 0.55 µs at any number of routes.

| Member | Purpose |
|---|---|
| `attachKeyRoute($route)` / `detachKeyRoute($route)` | index or remove a route |
| `routeKeyChange($key, $value)` | calls the enabled routes; skipped while behaviors are disabled |
| `__clone()`, `__wakeup()` | reset the index; re-attached behaviors register again |

## Patterns & Gotchas

- **Guard precedes `dyAddItem`** — the final check runs before `TMap::add()`, so behaviors such as `TMapRouteBehavior` never see a rejected change.
- **Same value still throws** — re-adding the identical value to a final key is rejected.
- **Append checks its key** — `add(null, $v)` and `$p[] = $v` check the next integer key PHP assigns (`getNextIntegerKey()`, from a copy that keeps PHP's append counter) when any key is final.
- **Custom `newParameters()`** — a subclass that returns a plain `TMap` cannot finalize; applying a configuration with a final parameter throws `TConfigurationException` (`appconfig_parameters_final_unsupported`). Non-final parameters still apply.
- **Serialization** — the final-key list serializes with the map and is omitted when empty.
