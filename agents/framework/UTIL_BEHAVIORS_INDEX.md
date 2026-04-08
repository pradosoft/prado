# Util/Behaviors/INDEX.md - UTIL_BEHAVIORS_INDEX.md

This file provides guidance to Agents when working with code in this repository.

## Purpose

Pre-built installable behaviors for common Prado application concerns. Each behavior extends `TBehavior` or `TClassBehavior` and can be attached via configuration or code.

## Behaviors

| Class | Attach To | Purpose |
|---|---|---|
| `TApplicationSignals` | `TApplication` | Connects POSIX signals (`SIGTERM`, `SIGINT`, `SIGHUP`) to application lifecycle events |
| `TBehaviorParameterLoader` | Any `TComponent` | Loads behavior configuration from a configuration parameter to be preset before the init phase |
| `TCaptureForkLog` | `TApplication` | Captures log entries from forked child processes |
| `TForkable` | Trait | Provides `fork()` helper for safe process forking with log capture |
| `TGlobalClassAware` | Any | Checks class availability across the application |
| `TMapLazyLoadBehavior` | `TMap` subclass | Lazy-loads TMap items on first access |
| `TMapRouteBehavior` | `TMap` subclass | Routes `TMap` read/write operations through a handler |
| `TNoUnserializeBehaviorTrait` | Behaviors | Trait preventing behavior from being unserialized (stateless behaviors) |
| `TPageGlobalizationCharsetBehavior` | `TPage` | Sets the page charset from `TGlobalization` settings |
| `TPageNoCacheBehavior` | `TPage` | Adds HTTP no-cache headers to page responses |
| `TPageTopAnchorBehavior` | `TPage` | Injects a `#top` anchor element at the top of the page |
| `TParameterizeBehavior` | Any `TComponent` | Loads property values from application parameters at init time |
| `TTimeZoneParameterBehavior` | `TApplication` | Sets PHP's default timezone from a behavior property or an application parameter |

## Conventions

- Behaviors in this directory are designed to be attached via `application.xml` using `TBehaviorsModule` or attached programmatically with `attachBehavior()`.
- Behaviors that should not retain per-instance state use `TNoUnserializeBehaviorTrait`.
- `TForkable` is a PHP trait, not a `TBehavior` — include it in classes that need process forking.
