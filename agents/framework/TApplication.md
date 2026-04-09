# TApplication

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
| `Normal` | Standard production mode; exceptions logged |
| `Performance` | Like Normal but skips cache freshness checks |

Set via `$app->setMode('Debug')` or in `application.xml`:
```xml
<application Mode="Debug">
```

## Lifecycle — 15 Stages

```
initApplication()
  → onInitComplete              ← modules initialized, services registered

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
| `Parameters` | `TAttributeCollection` of application-wide config parameters |
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
$app->getUser();                    // IUser
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
```

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

Even with an empty `application.xml`, these are always present:
- `THttpRequest` (id: `request`)
- `THttpResponse` (id: `response`)
- `TStatePersister` (page state serializer)
- `THttpSession` (id: `session`, lazy)
- `TSecurityManager` (id: `security`)
- `TAssetManager` (id: `asset`)
- `TPageService` (id: `page`)

## Patterns & Gotchas

- **Module init order matters** — modules initialize in declaration order. Dependencies (e.g., `TPermissionsManager` needs `TAuthManager`) must be declared after their dependencies.
- **`GlobalState`** — stored in a file in `RuntimePath`; loaded on `onLoadState`, saved on `onSaveState`. Don't store large data structures.
- **`onError` vs `onEndRequest`** — `onEndRequest` always runs; `onError` fires on exceptions. Both can be used for cleanup, but `onEndRequest` is safer for mandatory cleanup.
- **`Mode=Performance`** — templates and config are not recompiled even if changed. Only use in production with a proper deployment process.
