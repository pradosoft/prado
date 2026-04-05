# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Purpose

Cross-cutting utilities for the Prado framework: the behavior/mixin system, logging, scheduled tasks, RPC clients, POSIX signal handling, parameter modules, and general-purpose helpers.

## Behavior System

- **`IBaseBehavior`** / **`IBehavior`** / **`IClassBehavior`** / **`IDynamicMethods`** — Interfaces for the behavior system.

- **`TBaseBehavior`** — Base for all behaviors. Properties: `Name`, `Owner`. Tracks registered event handlers for cleanup. Supports serialization.

- **`TBehavior`** — Per-instance behavior (one owner, stateful). Owner stored as `WeakReference`. Override `attach($component)` and `detach($component)` for setup/teardown. Handlers registered in `attach()` are automatically removed in `detach()`.

- **`TClassBehavior`** — Class-wide behavior (stateless; applies to all instances of a class). Registered via `TComponent::attachClassBehavior()`. Does not store per-instance state.

- **`TBehaviorsModule`** — `TModule` that loads behavior configuration from `application.xml` and attaches behaviors to application components.

- **`TCallChain`** — AOP-style method call chain for `dy*` dynamic events. Behaviors returning from a `dy*` method can call `$chain->call(...)` to continue down the chain or return early to break it.

### Key Rule
When a behavior registers event handlers in `attach()`, it **must** unregister them in `detach()`. Use `$this->getOwner()->attachEventHandler(...)` / `detachEventHandler(...)`.

## Logging

- **`TLogger`** — Core logger. Access via `Prado::getLogger()`. Methods: `log($message, $level, $category)`. Levels: `TLogger::DEBUG`, `INFO`, `NOTICE`, `WARNING`, `ERROR`, `ALERT`, `FATAL`. Profiling: `TLogger::PROFILE_BEGIN`, `PROFILE_END` pairs matched by category. Auto-flushes when log count exceeds `AutoFlush` threshold (default: 10000). Fires `onFlushLogs` event on flush.

- **`TLogRouter`** — Module that routes log entries to multiple `TLogRoute` targets. Configured in `application.xml`.

- **`TLogRoute`** — Abstract base for log outputs. Subclass and implement `processLogs()`. Built-in routes:
  - `TFileLogRoute` — File output with rotation.
  - `TDbLogRoute` — Database logging.
  - `TEmailLogRoute` — Email alerts on error.
  - `TBrowserLogRoute` — Inline debug console on page.
  - `TStdOutLogRoute` — Console/stdout.
  - `TFirePhpLogRoute` / `TFirebugLogRoute` — Browser extension integration.
  - `TSysLogRoute` — System syslog.

## Parameter Modules

- **`TParameterModule`** — Stores named configuration parameters; access via `$app->getParameters()`.
- **`TDbParameterModule`** — DB-backed parameter store. Implements `IDbModule` and `IPermissions`. Loads parameters from a configurable table (supports WordPress `option_name`/`option_value` schema). Supports auto-capture of changes to `$app->getParameters()` back to the DB (`CaptureParameterChanges`). Serializes values via PHP serialize, JSON, or a custom callable. Used by `TPermissionsManager` for dynamic roles/permissions. Default SQLite if no `ConnectionID` specified.

## Plugin Modules

- **`TPluginModule`** — Base for Prado 4 composer-based extensions.
- **`TDbPluginModule`** — Plugin module with a database connection.

## RPC Clients

- **`TJsonRpcClient`** — JSON-RPC 2.0 client.
- **`TXmlRpcClient`** — XML-RPC client.
- **`TRpcClient`** — Base RPC implementation.
- **`TRpcClientRequestException`** / **`TRpcClientResponseException`** — RPC error types.

## Other Utilities

- **`TVarDumper`** — Human-readable variable dump for `TComponent` objects. Use instead of `var_dump()` for framework objects.
- **`TCallChain`** — See Behavior System above.
- **`TDataFieldAccessor`** — Dot-notation property path accessor (e.g., `"User.Profile.Name"`).
- **`TSignalsDispatcher`** — Singleton POSIX signal dispatcher. Translates OS signals into `fx*` global events. Special handling: `SIGCHLD` routes to per-PID handlers via `attachPidHandler()`; `SIGALRM` supports a time-based alarm queue (`alarm()` / `disarm()`). Prior handlers are saved and restored on `detach()`. Install via `TApplicationSignals` behavior (preferred) or `TSignalsDispatcher::singleton()->attach()`.
- **`TSignalParameter`** — Event parameter for signal events.
- **`TUtf8Converter`** — UTF-8 encoding conversions.
- **`TSimpleDateFormatter`** — Non-locale date formatting.
- **`TClassBehaviorEventParameter`** — Event parameter used when a class behavior is attached/detached.

## Subdirectories

| Directory | Purpose |
|---|---|
| `Behaviors/` | Pre-built installable behaviors (lazy loading, page behaviors, signals, etc.) |
| `Cron/` | Scheduled task engine: `TCronModule`, `TDbCronModule`, `TTimeScheduler` |
| `Helpers/` | Static utility classes: `TArrayHelper`, `TBitHelper`, `TProcessHelper` |
| `Math/` | `TRational`, `TURational` — rational number arithmetic |
