# INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Directories

[./](./INDEX.md)

| Directory | Purpose |
|---|---|
| [Caching/](Caching/INDEX.md) | Cache backends: [`TAPCCache`](Caching/TAPCCache.md), [`TMemCache`](Caching/TMemCache.md), [`TRedisCache`](Caching/TRedisCache.md), [`TDbCache`](Caching/TDbCache.md), [`TEtcdCache`](Caching/TEtcdCache.md). Unified [`ICache`](Caching/ICache.md) / [`TCache`](Caching/TCache.md) abstraction with dependency invalidation ([`TFileCacheDependency`](Caching/TFileCacheDependency.md), [`TDirectoryCacheDependency`](Caching/TDirectoryCacheDependency.md), [`TChainedCacheDependency`](Caching/TChainedCacheDependency.md), etc.) |
| [Collections/](Collections/INDEX.md) | [`TList`](Collections/TList.md), [`TMap`](Collections/TMap.md), [`TPriorityList`](Collections/TPriorityList.md), [`TPriorityMap`](Collections/TPriorityMap.md), [`TQueue`](Collections/TQueue.md), [`TStack`](Collections/TStack.md), [`TWeakList`](Collections/TWeakList.md) and interfaces |
| [Data/](Data/INDEX.md) | Database: [`TDbConnection`](Data/TDbConnection.md) (PDO wrapper), ActiveRecord ORM, DataGateway, SqlMap |
| [Exceptions/](Exceptions/INDEX.md) | Exception hierarchy, [`TErrorHandler`](Exceptions/TErrorHandler.md), multilingual error messages |
| [I18N/](I18N/INDEX.md) | [`TGlobalization`](I18N/TGlobalization.md), message translation, CultureInfo, date/number formatting |
| [IO/](IO/INDEX.md) | Text writers, [`TTarFileExtractor`](IO/TTarFileExtractor.md), stream notifications |
| [PHPStan/](PHPStan/INDEX.md) | Static analysis extensions for dynamic `dy*`/`fx*` methods and [`TComponent::isa()`](TComponent.md) |
| [Security/](Security/INDEX.md) | [`TAuthManager`](Security/TAuthManager.md), [`TUserManager`](Security/TUserManager.md), [`TSecurityManager`](Security/TSecurityManager.md), RBAC ([`TPermissionsManager`](Security/Permissions/TPermissionsManager.md)) |
| [Shell/](Shell/INDEX.md) | [`TShellApplication`](Shell/TShellApplication.md), [`TShellAction`](Shell/TShellAction.md), [`TShellWriter`](Shell/TShellWriter.md) — CLI application support |
| [Util/](Util/INDEX.md) | Logging, behaviors, cron, RPC clients, helpers, [`TVarDumper`](Util/TVarDumper.md), [`TCallChain`](Util/TCallChain.md) |
| [Web/](Web/INDEX.md) | HTTP layer, URL routing, asset management, UI controls, services, templates, javascript, active controls, jui, skins, themes |
| [Xml/](Xml/INDEX.md) | [`TXmlDocument`](Xml/TXmlDocument.md), [`TXmlElement`](Xml/TXmlElement.md) — DOM-compatible XML with XPath, ArrayAccess |

## Purpose

This is the root source directory of the **Prado** PHP framework (PSR-4 namespace `Prado\`). All framework classes live here and are registered in `framework/classes.php`.

> **Rule:** Every new class added to any subdirectory must also be added to `framework/classes.php`.

## Top-Level Files

- **[`TComponent.php`](TComponent.md)** — Base class for nearly everything (~89KB). Implements:
  - Property system via `getXxx()`/`setXxx()` magic (`__get`, `__set`, `__isset`, `__unset`)
  - Event system: `attachEventHandler()`, `raiseEvent()`, `detachEventHandler()`
  - Behavior attachment: `attachBehavior()`, `detachBehavior()`, `enableBehavior()`
  - Dynamic events: `__call()` dispatches `dy*` (attached behavior events)
  - Serialization: `__sleep()`, `__wakeup()`, `_getZappableSleepProps()`
  - Cloning: `__clone()` with `dyClone` dynamic event

- **[`TApplication.php`](TApplication.md)** — Top-level service container. Manages modules, services, configuration, and the application lifecycle. Entry point for every request.

- **[`TApplicationComponent.php`](TApplicationComponent.md)** — Base for application-aware components; provides `getApplication()`, `getService()`, `getRequest()`, `getResponse()`, `getSession()`, `getUser()`.

- **[`TApplicationConfiguration.php`](TApplicationConfiguration.md)** — Parses `application.xml` (or `application.php`); loads module/service/parameter definitions.

- **[`TModule.php`](TModule.md)** — Base for pluggable application modules registered in configuration.

- **[`TService.php`](TService.md)** — Base for application services (page, JSON, RPC, SOAP, feed).

- **[`TEventHandler.php`](TEventHandler.md)** — Invokable wrapper for event handlers; supports hierarchical invokable data.

- **[`TEventSubscription.php`](TEventSubscription.md)** — Temporarily subscribes a handler to an `on*` event; auto-removes on destruct.

- **`TPropertyValue.php`** — Static type-conversion utilities used in property setters:
  - `ensureBoolean()`, `ensureInteger()`, `ensureFloat()`, `ensureString()`, `ensureArray()`, `ensureEnum()`

- **`TComponentReflection.php`** — Introspection utilities for [`TComponent`](TComponent.md) property/event metadata.

- **[`Prado.php`](Prado.md)** — Static framework utility class: `Prado::getApplication()`, `Prado::getLogger()`, `Prado::getUser()`, `Prado::log()`, `Prado::setPathOfAlias()`, `Prado::getPathOfAlias()`, `Prado::using()`.

- **`classes.php`** — PHP array listing every framework class and its namespace path. **Must be updated for every new class.**

## Key Conventions

- **`on` prefix** — standard events (e.g., `onInit`, `onLoad`, `onLogin`).
- **`dy` prefix** — dynamic events dispatched to attached behaviors (e.g., `dyShouldContinue`, `dyValidate`).
- **`fx` prefix** — global events auto-registered based on `getAutoGlobalListen()` (e.g., `fxAttachClassBehavior`).
- **`@method` PHPDoc** — used on classes to document dynamic `dy*` events that aren't explicitly defined.
- **`@since 4.3.3`** — tag all new files, classes, and methods with the next release version.
- **`if` blocks** — always use `{}`, never single-line bodies.
- **Property setters** — use `TPropertyValue::ensureXxx()` for type coercion in Configuration and templates setters.
