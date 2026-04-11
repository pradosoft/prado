# Util/INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Directories
[framework](./INDEX.md) / **`Util/INDEX.md`**

| Directory | Purpose |
|---|---|
| [Behaviors/](Behaviors/INDEX.md) | Pre-built installable behaviors (lazy loading, page behaviors, signals, etc.) |
| [Cron/](Cron/INDEX.md) | Scheduled task engine: [`TCronModule`](Cron/TCronModule.md), [`TDbCronModule`](Cron/TDbCronModule.md), [`TTimeScheduler`](Cron/TTimeScheduler.md) |
| [Helpers/](Helpers/INDEX.md) | Static utility classes: [`TArrayHelper`](Helpers/TArrayHelper.md), [`TBitHelper`](Helpers/TBitHelper.md), [`TProcessHelper`](Helpers/TProcessHelper.md) |
| [Math/](Math/INDEX.md) | [`TRational`](Math/TRational.md), [`TURational`](Math/TURational.md) — rational number arithmetic |

## Purpose

Cross-cutting utilities for the Prado framework: the behavior/mixin system, logging, scheduled tasks, RPC clients, POSIX signal handling, parameter modules, and general-purpose helpers.

## Behavior System

- **[`IBaseBehavior`](IBaseBehavior.md)** / **[`IBehavior`](IBehavior.md)** / **[`IClassBehavior`](IClassBehavior.md)** / **[`IDynamicMethods`](IDynamicMethods.md)** — Interfaces for the behavior system.

- **[`TBaseBehavior`](TBaseBehavior.md)** — Base for all behaviors. Properties: `Name`, `Owner`. Tracks registered event handlers for cleanup. Supports serialization.

- **[`TBehavior`](TBehavior.md)** — Per-instance behavior (one owner, stateful). Owner stored as `WeakReference`. Override `attach($component)` and `detach($component)` for setup/teardown. Handlers registered in `attach()` are automatically removed in `detach()`.

- **[`TClassBehavior`](TClassBehavior.md)** — Class-wide behavior (stateless; applies to all instances of a class). Registered via [`TComponent::attachClassBehavior()`](../TComponent.md). Does not store per-instance state.

- **[`TBehaviorsModule`](TBehaviorsModule.md)** — [`TModule`](../TModule.md) that loads behavior configuration from `application.xml` and attaches behaviors to application components.

- **[`TCallChain`](TCallChain.md)** — AOP-style method call chain for `dy*` dynamic events. Behaviors returning from a `dy*` method can call `$chain->call(...)` to continue down the chain or return early to break it.

### Key Rule
When a behavior registers event handlers in `attach()`, it **must** unregister them in `detach()`. Use `$this->getOwner()->attachEventHandler(...)` / `detachEventHandler(...)`.

## Logging

- **[`TLogger`](TLogger.md)** — Core logger. Access via `Prado::getLogger()`. Methods: `log($message, $level, $category)`. Levels: `TLogger::DEBUG`, `INFO`, `NOTICE`, `WARNING`, `ERROR`, `ALERT`, `FATAL`. Profiling: `TLogger::PROFILE_BEGIN`, `PROFILE_END` pairs matched by category. Auto-flushes when log count exceeds `AutoFlush` threshold (default: 10000). Fires `onFlushLogs` event on flush.

- **[`TLogRouter`](TLogRouter.md)** — Module that routes log entries to multiple [`TLogRoute`](TLogRoute.md) targets. Configured in `application.xml`.

- **[`TLogRoute`](TLogRoute.md)** — Abstract base for log outputs. Subclass and implement `processLogs()`. Built-in routes:
  - `TFileLogRoute` — File output with rotation.
  - `TDbLogRoute` — Database logging.
  - `TEmailLogRoute` — Email alerts on error.
  - `TBrowserLogRoute` — Inline debug console on page.
  - `TStdOutLogRoute` — Console/stdout.
  - `TFirePhpLogRoute` / `TFirebugLogRoute` — Browser extension integration.
  - `TSysLogRoute` — System syslog.

## Parameter Modules

- **[`TParameterModule`](TParameterModule.md)** — Stores named configuration parameters; access via `$app->getParameters()`.
- **[`TDbParameterModule`](TDbParameterModule.md)** — DB-backed parameter store. Implements [`IDbModule`](IDbModule.md) and [`IPermissions`](../Security/Permissions/IPermissions.md). Loads parameters from a configurable table (supports WordPress `option_name`/`option_value` schema). Supports auto-capture of changes to `$app->getParameters()` back to the DB (`CaptureParameterChanges`). Serializes values via PHP serialize, JSON, or a custom callable. Used by [`TPermissionsManager`](../Security/Permissions/TPermissionsManager.md) for dynamic roles/permissions. Default SQLite if no `ConnectionID` specified.

## Plugin Modules

- **[`TPluginModule`](TPluginModule.md)** — Base for Prado 4 composer-based extensions.
- **[`TDbPluginModule`](TDbPluginModule.md)** — Plugin module with a database connection.

## RPC Clients

- **[`TJsonRpcClient`](TJsonRpcClient.md)** — JSON-RPC 2.0 client.
- **[`TXmlRpcClient`](TXmlRpcClient.md)** — XML-RPC client.
- **[`TRpcClient`](TRpcClient.md)** — Base RPC implementation.
- **`TRpcClientRequestException`** / **`TRpcClientResponseException`** — RPC error types.

## Other Utilities

- **[`TVarDumper`](TVarDumper.md)** — Human-readable variable dump for [`TComponent`](../TComponent.md) objects. Use instead of `var_dump()` for framework objects.
- **[`TCallChain`](TCallChain.md)** — See Behavior System above.
- **[`TDataFieldAccessor`](TDataFieldAccessor.md)** — Dot-notation property path accessor (e.g., `"User.Profile.Name"`).
- **[`TSignalsDispatcher`](TSignalsDispatcher.md)** — Singleton POSIX signal dispatcher. Translates OS signals into `fx*` global events. Special handling: `SIGCHLD` routes to per-PID handlers via `attachPidHandler()`; `SIGALRM` supports a time-based alarm queue (`alarm()` / `disarm()`). Prior handlers are saved and restored on `detach()`. Install via [`TApplicationSignals`](Behaviors/TApplicationSignals.md) behavior (preferred) or `TSignalsDispatcher::singleton()->attach()`.
- **[`TSignalParameter`](TSignalParameter.md)** — Event parameter for signal events.
- **[`TUtf8Converter`](TUtf8Converter.md)** — UTF-8 encoding conversions.
- **`TSimpleDateFormatter`** — Non-locale date formatting.
- **[`TClassBehaviorEventParameter`](TClassBehaviorEventParameter.md)** — Event parameter used when a class behavior is attached/detached.
