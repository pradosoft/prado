# I18N/TGlobalizationProxy

### Directories
[framework](../INDEX.md) / [I18N](./INDEX.md) / **`TGlobalizationProxy`**

## Class Info
**Location:** `framework/I18N/TGlobalizationProxy.php`
**Namespace:** `Prado\I18N`
**Extends:** [`TGlobalization`](./TGlobalization.md); implements `IModuleDependency`, [`IProxy`](../IProxy.md); uses [`TComponentProxyTrait`](../TComponentProxyTrait.md) (@since 4.4.0)

## Overview
Transparent globalization module that delegates every public `TGlobalization` property and method to the `TGlobalization` module named by `BackingGlobalizationId`. Lets the application globalization slot be swapped in configuration without touching consumers.

## Configuration

```xml
<modules>
    <module id="globalization" class="Prado\I18N\TGlobalizationProxy" BackingGlobalizationId="realGlobalization" />
    <module id="realGlobalization" class="Prado\I18N\TGlobalization" DefaultCulture="en_US">
        <translation type="gettext" source="Application.messages" autosave="true" cache="true" />
    </module>
</modules>
```

The `<translation>` element belongs on the backing module. A `<translation>` element, or a `translate`/`translation` key in a PHP configuration, on the proxy throws `globalizationproxy_translation_not_allowed` at `init()`.

## Properties

- `BackingGlobalizationId` (string) — required by `init()` (`globalizationproxy_backing_globalization_id_required`). Replacing a non-empty ID detaches the event forwarders, logs a `TLogger::WARNING` (`prado.i18n`), and drops the resolved backing.
- `Globalization` (read-only `TGlobalization`) — resolves the backing on first call; throws `globalizationproxy_globalization_not_found` or `globalizationproxy_invalid_globalization_type`, then calls `attachProxy()`.

## Behavior

- `init()` registers the proxy as `$app->getGlobalization()` (`setAppGlobalization()`) and raises `dyInit`; it does not call `TGlobalization::init()`, so the proxy holds no culture, charset, or translation state of its own.
- Every public `TGlobalization` method is overridden to delegate: `TranslateDefaultCulture`, `DefaultCulture`, `DefaultCharset`, `Culture`, `Charset`, `TranslationConfiguration`, `TranslationCatalogue`, `IsCultureRTL`, `getCultureVariants()`, `getLocalizedResource()`. Base-class methods are not reached by `__call`, so each override is required.
- Other backing properties, methods, and `on` events reach the backing through `TComponentProxyTrait`; `dy` and `fx` names are never forwarded.
- `getModuleDependencies()` returns the backing ID so the backing initializes first.
- `_getZappableSleepProps()` excludes the forwarder list, the resolved backing, and an empty backing ID; the backing is re-resolved from the module registry after unserialization.

## See Also
- [TGlobalization](./TGlobalization.md), [TCacheProxy](../Caching/TCacheProxy.md), [TModuleProxy](../TModuleProxy.md)
