# TApplication

### Directories
[framework](./INDEX.md) / **`TApplication`**

## Class Info
**Location:** `framework/TApplication.php`
**Namespace:** `Prado`
**Extends:** `TComponent`

## Overview
The top-level service container for every Prado request. Loads `application.xml`, initializes all configured modules, determines which service handles the request, runs the request lifecycle, and flushes output. Entry point: `$app->run()`.

## Application Modes (TApplicationMode)

| Mode | Behavior |
|------|----------|
| `Off` | Refuses all requests |
| `Debug` | Cache always updated; verbose errors; debug info shown |
| `Normal` | Standard production mode; exceptions logged; the default (`DEFAULT_APPLICATION_MODE`, 4.4.0) |
| `Performance` | Like Normal but skips cache freshness checks; keeps class fx-event lists in `runtime/fxevent.cache`, refreshed on a Composer change and cleared by `prado-cli cache/flush-all` |

An application without `Mode` runs in `Normal`; in 4.3 it ran in `Debug`. Set via `$app->setMode('Debug')` or in `application.xml`:
```xml
<application Mode="Debug">
```

## Lifecycle — 16 Stages

```
initApplication()
  → onConfiguration             ← config fully applied, no service started yet (@since 4.3.3)
  → onInitComplete              ← service initialized

run() → processRequest()
  → onBeginRequest              ← request parsing complete
  → onLoadState                 ← load page/session state
  → onLoadStateComplete
  → onAuthentication            ← TAuthManager sets current user
  → onAuthenticationComplete
  → onAuthorization             ← authorization rules checked
  → onAuthorizationComplete
  → onPreRunService
  → runService()                ← TPageService/TJsonService/etc. runs
  → onSaveState                 ← persist session/page state
  → onSaveStateComplete
  → onPreFlushOutput
  → flushOutput()               ← send HTTP response
  → onEndRequest                ← always runs (cleanup, logging flush)
```

`onError` fires whenever an unhandled exception escapes any stage.

## Key Properties

| Property | Description |
|----------|-------------|
| `Mode` | `TApplicationMode` enum value |
| `RuntimePath` | Writable path for cache/session files (default: `protected/runtime`) |
| `UniqueID` | Stable hash identifying this application; used for cache key prefixes |
| `Parameters` | [`TApplicationParameters`](TApplicationParameters.md) of application-wide config parameters; `final="true"` locks a parameter |
| `Modules` | Map of all registered modules |
| `Services` | Map of registered services |

## Key Methods / Accessors

```php
$app = Prado::getApplication();

// Modules:
$app->getModule('cache');           // TCache or any module by ID
$app->getModule('auth');            // TAuthManager
$app->setModule($id, $module);

// Core services (shorthand accessors):
$app->getRequest();                 // THttpRequest
$app->getResponse();                // THttpResponse
$app->getSession();                 // THttpSession
$app->getUser();                    // IUser; the fiber's user (TFiberContext, USER_CONTEXT_KEY) first, as in a cron task
$app->getCache();                   // ICache (primary cache)
$app->getSecurityManager();         // TSecurityManager
$app->getService();                 // current IService

// Global state (persisted between requests):
$app->getGlobalState($key, $default);
$app->setGlobalState($key, $value, $default);
$app->clearGlobalState($key);

// Locale / culture:
$app->getGlobalization();           // TGlobalization or null
$app->getCulture();                 // current culture string

// Service lookup by class (@since 4.3.3):
$app->getServiceIdByClass(TPageService::class);   // first matching service ID or null
$app->getServiceIdsByClass(TPageService::class);  // all matching service IDs

// Module lookup by class (template-typed; @since 4.2.0):
// Returns array<string, ?T> — keys are module IDs, values are module instances (or null if not yet loaded)
$app->getModulesByType(ICache::class);            // all ICache modules
$app->getModulesByType(TDbCache::class, strict: true); // only exact TDbCache (no subclasses)
```

## Services

`registerService($id, $class, $properties, $config)` stores a service class under an ID; `<service>` elements in `application.xml` register the same way. `startService($id)` creates the instance, applies `$properties` with `TComponent::setSubProperties()`, and calls `init($config)`. A service class implements `IService` and extends `TComponent`; `TService` does both.

| Condition | Method | Result |
|---|---|---|
| Class name is null or empty | `registerService()` | `TConfigurationException` `application_service_class_required` |
| Class does not exist | `registerService()` | `TConfigurationException` `application_service_class_not_found` |
| Class does not implement `IService` | `registerService()` | `TConfigurationException` `application_service_class_not_service` |
| ID is not registered | `startService()` | `THttpException` 404 `application_service_unknown` |
| Instance does not implement `IService` | `startService()` | `TConfigurationException` `application_service_invalid` |
| Instance implements `IService` and does not extend `TComponent` | `startService()` | `TConfigurationException` `application_service_not_component` |
| `getEnabled()` is false | `startService()` | `TConfigurationException` `application_service_unavailable` |

`registerService()` does not check for `TComponent`, so an `IService` class that does not extend it registers and fails when it is started.

## Configuration (application.xml)

```xml
<application id="MyApp" Mode="Debug">
    <paths>
        <using namespace="Application.Pages" />
    </paths>
    <modules>
        <module id="db" class="Prado\Data\TDataSourceConfig"
                ConnectionString="mysql:host=localhost;dbname=myapp"
                Username="root" Password="secret" />
        <module id="cache" class="Prado\Caching\TRedisCache" Host="localhost" />
        <module id="auth" class="Prado\Security\TAuthManager"
                UserManager="users" LoginPage="Login" />
    </modules>
    <services>
        <service id="page" class="Prado\Web\Services\TPageService" />
    </services>
    <parameters>
        <parameter id="AppVersion" value="1.0.0" />
        <parameter id="DeveloperMode" value="0" final="true" />
    </parameters>
</application>
```

## Bootstrapping

```php
// index.php:
require 'vendor/autoload.php';
$app = new Prado\TApplication('/path/to/protected');
$app->run();
```

## Default Modules (Auto-Loaded)

Even with an empty `application.xml`, each accessor creates its default module on first use; `bootstrapModule()` assigns the `DEFAULT_*_ID` constant as its ID and runs its lifecycle with a `null` configuration.

| Accessor | Default class | `DEFAULT_*_ID` |
|---|---|---|
| `getRequest()` | `THttpRequest` | `request` |
| `getResponse()` | `THttpResponse` | `response` |
| `getSession()` | `THttpSession` | `session` |
| `getErrorHandler()` | `TErrorHandler` | `errorHandler` |
| `getSecurityManager()` | `TSecurityManager` | `securityManager` |
| `getAssetManager()` | `TAssetManager` | `assetManager` |
| `getGlobalization()` | `TGlobalization` | `globalization` |
| `getTemplateManager()` | `TTemplateManager` | `templateManager` |
| `getThemeManager()` | `TThemeManager` | `themeManager` |

`TPageService` (id `page`) is the default service, not a module.

A default enters `$_modules` through `registerDefaultModule()` only when no same-type module is configured (`shouldRegisterDefault()`; `bootstrapDefaultModules()` sweeps the instanced defaults after the configuration is applied, and the accessors register later ones) and the ID is free. A configured module registered under the same ID, loaded or pending as lazy, keeps it: `getModule('session')` returns the configured module and `getSession()` returns the default. `setModule()` throws `application_moduleid_duplicated` for a duplicate ID; a default yields instead.

## Extensibility Hooks (@since 4.3.3)

These protected factory methods allow subclasses to substitute custom implementations:

```php
protected function getApplicationConfigurationClass(): string
// Returns the class name used to parse application.xml/application.php.
// Override to provide a custom configuration parser.
// Default: TApplicationConfiguration::class

protected function newApplicationStatePersister(): IStatePersister
// Creates the state persister used for GlobalState storage.
// Override to use a different persistence backend.
// Default: new TApplicationStatePersister()
```

## Patterns & Gotchas

- **Module init order** — eager modules initialize in declaration order, reordered by the dependencies a module or its behavior declares through `IModuleDependency` (`sortModulesByDependency()`, Kahn sort; a cycle throws `application_module_dependency_cycle`). Before each module's `init()`, `resolveModuleDependencies()` applies one rule on the eager path (`applyConfiguration()`), the lazy path (`getModule()`) and the default modules (`bootstrapModule()`): a registered dependency is loaded first (a lazy one is force-loaded), a missing required dependency throws `TConfigurationException` `application_module_required_dep_missing`, a missing advisory dependency is skipped. A dependency on a module of a later configuration batch (an external file or a service configuration) is missing at that time.
- **`GlobalState`** — stored in a file in `RuntimePath`; loaded on `onLoadState`, saved on `onSaveState`. Don't store large data structures.
- **`onError` vs `onEndRequest`** — `onEndRequest` always runs; `onError` fires on exceptions. Both can be used for cleanup, but `onEndRequest` is safer for mandatory cleanup.
- **`Mode=Performance`** — templates and config are not recompiled even if changed. Only use in production with a proper deployment process.
- **Module that does not extend `TComponent`** — an `IModule` alone loads on the eager and lazy paths: `applyModuleProperties()` sets each configured property through its public setter, coerced by `TPropertyValue::coerceForSetter()`; a property without a setter throws `TInvalidOperationException` `component_property_undefined`. `dyPreInit` and `dyPostInit` are raised only on a `TComponent` module.
- **Module `lazy` attribute** — read with `TPropertyValue::ensureBoolean()`: `true` or a non-zero number defers the module until it is requested; `false` loads it at once. A module that another module declares as a dependency is requested before that module's `init()`.
