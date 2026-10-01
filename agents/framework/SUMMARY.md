# Framework/SUMMARY.md

Root source directory of the Prado PHP framework (PSR-4 namespace `Prado\`).

The IO utilities include TAR extraction with cleanup for every `Throwable`, including manifest-only scans.

The exception message catalogs use the English catalog as the master and remove obsolete keys from language variants.

SqlMap result maps wrap type-handler construction failures with mapping context.

## Classes

- **`TComponent`** — Base class for nearly everything; provides property system (`getXxx()`/`setXxx()`), event system (`attachEventHandler()`/`raiseEvent()`), behavior attachment, dynamic events (`dy*` prefix), and serialization support.

- **`TApplication`** — Top-level service container managing modules, services, configuration, and the application lifecycle; entry point for every request.

- **`TApplicationComponent`** — Base for application-aware components; provides `getApplication()`, `getService()`, `getRequest()`, `getResponse()`, `getSession()`, `getUser()`.

- **`TApplicationConfiguration`** — Parses `application.xml` or `application.php`; loads module/service/parameter definitions.

- **`TModule`** — Base class for pluggable application modules registered in configuration.

- **`TService`** — Base class for application services (page, JSON, RPC, SOAP, feed).

- **`TEventHandler`** — Invokable wrapper for event handlers; supports hierarchical invokable data.

- **`TEventSubscription`** — Temporarily subscribes a handler to an `on*` event; auto-removes on destruct.

- **`TPropertyValue`** — Static type-conversion utilities: `ensureBoolean()`, `ensureInteger()`, `ensureFloat()`, `ensureString()`, `ensureArray()`, `ensureEnum()`.

- **`TComponentReflection`** — Introspection utilities for TComponent property/event metadata.

- **`Prado`** — Static framework utility class: `Prado::getApplication()`, `Prado::getLogger()`, `Prado::getUser()`, `Prado::log()`, `Prado::setPathOfAlias()`, `Prado::getPathOfAlias()`, `Prado::using()`.
