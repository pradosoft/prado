# Util/Behaviors/SUMMARY.md

Pre-built installable behaviors for common Prado application concerns; each behavior extends `TBehavior` or `TClassBehavior`.

## Classes

- **`TApplicationSignals`** — Attaches to `TApplication`; connects POSIX signals (`SIGTERM`, `SIGINT`, `SIGHUP`) to application lifecycle events.

- **`TBehaviorParameterLoader`** — Attaches to any `TComponent`; loads behavior configuration from a configuration parameter preset before init phase.

- **`TCaptureForkLog`** — Attaches to `TApplication`; captures log entries from forked child processes.

- **`TForkable`** — Trait providing `fork()` helper for safe process forking with log capture.

- **`TGlobalClassAware`** — Attaches to any class; checks class availability across the application.

- **`TMapLazyLoadBehavior`** — Attaches to `TMap` subclass; lazy-loads TMap items on first access.

- **`TMapRouteBehavior`** — Attaches to `TMap` subclass; routes `TMap` read/write operations through a handler.

- **`TNoUnserializeBehaviorTrait`** — Trait preventing behavior from being unserialized (stateless behaviors).

- **`TPageGlobalizationCharsetBehavior`** — Attaches to `TPage`; sets page charset from `TGlobalization` settings.

- **`TPageNoCacheBehavior`** — Attaches to `TPage`; adds HTTP no-cache headers to page responses.

- **`TPageTopAnchorBehavior`** — Attaches to `TPage`; injects a `#top` anchor element at top of page.

- **`TParameterizeBehavior`** — Attaches to any `TComponent`; loads property values from application parameters at init time.

- **`TTimeZoneParameterBehavior`** — Attaches to `TApplication`; sets PHP's default timezone from behavior property or parameter.
