# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Purpose

This is the root source directory of the **Prado** PHP framework (PSR-4 namespace `Prado\`). All framework classes live here and are registered in `framework/classes.php`.

> **Rule:** Every new class added to any subdirectory must also be added to `framework/classes.php`.

## Top-Level Files

- **`TComponent.php`** — Base class for nearly everything (~89KB). Implements:
  - Property system via `getXxx()`/`setXxx()` magic (`__get`, `__set`, `__isset`, `__unset`)
  - Event system: `attachEventHandler()`, `raiseEvent()`, `detachEventHandler()`
  - Behavior attachment: `attachBehavior()`, `detachBehavior()`, `enableBehavior()`
  - Dynamic events: `__call()` dispatches `dy*` (behavior events) and `fx*` (global events)
  - Serialization: `__sleep()`, `__wakeup()`, `_getZappableSleepProps()`
  - Cloning: `__clone()` with `dyClone` dynamic event

- **`TApplication.php`** — Top-level service container. Manages modules, services, configuration, and the application lifecycle. Entry point for every request.

- **`TApplicationComponent.php`** — Base for application-aware components; provides `getApplication()`, `getService()`, `getRequest()`, `getResponse()`, `getSession()`, `getUser()`.

- **`TApplicationConfiguration.php`** — Parses `application.xml` (or PHP equivalent); loads module/service/parameter definitions.

- **`TModule.php`** — Base for pluggable application modules registered in configuration.

- **`TService.php`** — Base for application services (page, JSON, RPC, SOAP, feed).

- **`TEventHandler.php`** — Invokable wrapper for event handlers; supports priority-based dispatch and hierarchical handler data.

- **`TEventSubscription.php`** — Temporarily subscribes a handler to an `on*` event; auto-removes on destruct.

- **`TPropertyValue.php`** — Static type-conversion utilities used in property setters:
  - `ensureBoolean()`, `ensureInteger()`, `ensureFloat()`, `ensureString()`, `ensureArray()`, `ensureEnum()`

- **`TComponentReflection.php`** — Introspection utilities for TComponent property/event metadata.

- **`Prado.php`** — Static framework utility class: `Prado::getApplication()`, `Prado::getLogger()`, `Prado::getUser()`, `Prado::log()`, `Prado::setPathOfAlias()`, `Prado::getPathOfAlias()`, `Prado::using()`.

- **`classes.php`** — PHP array listing every framework class and its namespace path. **Must be updated for every new class.**

## Subdirectories

| Directory | Purpose |
|---|---|
| `Caching/` | Cache backends: `TAPCCache`, `TMemCache`, `TRedisCache`, `TDbCache`, `TEtcdCache`. Unified `ICache` / `TCache` abstraction with dependency invalidation (`TFileCacheDependency`, `TDirectoryCacheDependency`, `TChainedCacheDependency`, etc.) |
| `Collections/` | TList, TMap, TPriorityList, TPriorityMap, TQueue, TStack, TWeakList and interfaces |
| `Data/` | Database: TDbConnection (PDO wrapper), ActiveRecord ORM, DataGateway, SqlMap |
| `Exceptions/` | Exception hierarchy, TErrorHandler, multilingual error messages |
| `I18N/` | TGlobalization, message translation, CultureInfo, date/number formatting |
| `IO/` | Text writers, TTarFileExtractor, stream notifications |
| `PHPStan/` | Static analysis extensions for dynamic `dy*`/`fx*` methods and `TComponent::isa()` |
| `Security/` | TAuthManager, TUserManager, TSecurityManager, RBAC (TPermissionsManager) |
| `Shell/` | TShellApplication, TShellAction, TShellWriter — CLI application support |
| `Util/` | Logging, behaviors, cron, RPC clients, helpers, TVarDumper, TCallChain |
| `Web/` | HTTP layer, URL routing, asset management, UI controls, services |
| `Xml/` | TXmlDocument, TXmlElement — DOM-compatible XML with XPath, ArrayAccess |

## Key Conventions

- **`dy` prefix** — dynamic events dispatched to attached behaviors (e.g., `dyShouldContinue`, `dyValidate`).
- **`fx` prefix** — global events auto-registered based on `getAutoGlobalListen()` (e.g., `fxAttachClassBehavior`).
- **`on` prefix** — standard lifecycle events (e.g., `onInit`, `onLoad`, `onPreRender`).
- **`@method` PHPDoc** — used on classes to document dynamic `dy*` events that aren't explicitly defined.
- **`@since 4.3.3`** — tag all new files, classes, and public methods with the next release version.
- **`if` blocks** — always use `{}`, never single-line bodies.
- **Property setters** — use `TPropertyValue::ensureXxx()` for type coercion.
