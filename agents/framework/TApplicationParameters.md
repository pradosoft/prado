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
| `itemAt()`, `$p[$key]`, `contains()` | value / true |
| `add()`, `$p[$key] = $v`, `mergeWith()` | throws `TInvalidOperationException` (`appparameters_final`) |
| `remove()`, `unset($p[$key])`, `removeItem()`, `clear()`, `copyFrom()` | throws `appparameters_final` |
| `finalize()` again | no change |

## Patterns & Gotchas

- **Guard precedes `dyAddItem`** — the final check runs before `TMap::add()`, so behaviors such as `TMapRouteBehavior` never see a rejected change.
- **Same value still throws** — re-adding the identical value to a final key is rejected.
- **Custom `newParameters()`** — a subclass that returns a plain `TMap` cannot finalize; applying a configuration with a final parameter throws `TConfigurationException` (`appconfig_parameters_final_unsupported`). Non-final parameters still apply.
- **Serialization** — the final-key list serializes with the map and is omitted when empty.
