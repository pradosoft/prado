# Util/Behaviors/INDEX.md

### Directories
[framework](./INDEX.md) / [Util](./Util/INDEX.md) / **`Behaviors/INDEX.md`**

## Purpose

Pre-built installable behaviors for common Prado application concerns. Each behavior extends [`TBehavior`](../TBehavior.md) or [`TClassBehavior`](../TClassBehavior.md) and can be attached via configuration or code.

## Behaviors

| Class | Attach To | Purpose |
|---|---|---|
| `TApplicationSignals` | [`TApplication`](../TApplication.md) | Connects POSIX signals (`SIGTERM`, `SIGINT`, `SIGHUP`) to application lifecycle events |
| `TBehaviorParameterLoader` | Any [`TComponent`](../TComponent.md) | Loads behavior configuration from a configuration parameter to be preset before the init phase |
| `TCaptureForkLog` | [`TApplication`](../TApplication.md) | Captures log entries from forked child processes |
| `TForkable` | Trait | Provides `fork()` helper for safe process forking with log capture |
| `TGlobalClassAware` | Any | Checks class availability across the application |
| `TMapLazyLoadBehavior` | [`TMap`](../Collections/TMap.md) subclass | Lazy-loads TMap items on first access |
| `TMapRouteBehavior` | [`TMap`](../Collections/TMap.md) subclass | Routes `TMap` read/write operations through a handler |
| `TNoUnserializeBehaviorTrait` | Behaviors | Trait preventing behavior from being unserialized (stateless behaviors) |
| `TPageGlobalizationCharsetBehavior` | [`TPage`](../Web/UI/TPage.md) | Sets the page charset from [`TGlobalization`](../I18N/TGlobalization.md) settings |
| `TPageNoCacheBehavior` | [`TPage`](../Web/UI/TPage.md) | Adds HTTP no-cache headers to page responses |
| `TPageTopAnchorBehavior` | [`TPage`](../Web/UI/TPage.md) | Injects a `#top` anchor element at the top of the page |
| `TParameterizeBehavior` | Any [`TComponent`](../TComponent.md) | Loads property values from application parameters at init time |
| `TTimeZoneParameterBehavior` | [`TApplication`](../TApplication.md) | Sets PHP's default timezone from a behavior property or an application parameter |

## Conventions

- Behaviors in this directory are designed to be attached via `application.xml` using [`TBehaviorsModule`](../Util/TBehaviorsModule.md) or attached programmatically with `attachBehavior()`.
- Behaviors that should not retain per-instance state use `TNoUnserializeBehaviorTrait`.
- `TForkable` is a PHP trait, not a `TBehavior` — include it in classes that need process forking.
