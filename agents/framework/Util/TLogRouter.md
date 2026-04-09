# TLogRouter

### Directories

[Util](../) > TLogRouter

**Location:** `framework/Util/TLogRouter.php`
**Namespace:** `Prado\Util`

## Overview

`TLogRouter` is a `[TModule](TModule.md)` that manages multiple `[TLogRoute](TLogRoute.md)` instances. It acts as the bridge between the `[TLogger](TLogger.md)` (which accumulates log entries in memory) and the output destinations (files, database, email, browser panel, etc.).

On `init()`, it attaches to `[TLogger](TLogger.md)::onFlushLogs` so it receives logs whenever the logger flushes (end-of-request or manual flush).

## Configuration

```xml
<module id="log" class="Prado\Util\TLogRouter">
    <route class="Prado\Util\TFileLogRoute"
           Levels="Warning|Error"
           File="protected/runtime/app.log" />
    <route class="Prado\Util\TBrowserLogRoute"
           Levels="Debug|Info|Warning|Error" />
    <route class="Prado\Util\TEmailLogRoute"
           Levels="Fatal"
           SentTo="admin@example.com" />
</module>
```

Alternatively, routes can be loaded from an external file:

```xml
<module id="log" class="Prado\Util\TLogRouter"
        ConfigFile="Application.Config.LogConfig" />
```

## Key Properties

| Property | Type | Description |
|----------|------|-------------|
| `ConfigFile` | string | Path alias to external routes config XML/PHP file |
| `FlushCount` | int | Flush to routes after this many log entries (default: 0 = flush only on route's `ProcessInterval`) |
| `TraceLevel` | int | Number of call-stack frames to include in log messages (default: 0) |

## Key Methods

```php
$router->addRoute([TLogRoute](TLogRoute.md) $route, $config = null): void
$router->removeRoute([TLogRoute](TLogRoute.md) $route): ?[TLogRoute](TLogRoute.md)
$router->getRoutes(): array                  // all registered [TLogRoute](TLogRoute.md) instances
$router->getRoutesCount(): int
$router->collectLogs([TLogger](TLogger.md) $logger, bool $final): void  // called by [TLogger](TLogger.md)::onFlushLogs
```

## Patterns & Gotchas

- **Multiple routes of the same type** — allowed; each can have different `Levels`/`Categories` filters.
- **`collectLogs()` is the flush handler** — attached to `TLogger::onFlushLogs` in `init()`. Each route's `collectLogs()` is called in turn; routes that haven't reached `ProcessInterval` accumulate logs for later.
- **`TraceLevel`** — setting this to `> 0` appends PHP call-stack frames to log messages. Useful for debugging but adds overhead.
- **External config file** — `ConfigFile` is merged with inline `<route>` elements; both can coexist.
- **`[IOutputLogRoute](IOutputLogRoute.md)`** — routes implementing this interface (e.g., `TBrowserLogRoute`) are called at a special time during page rendering to inject their output; other routes flush at `onEndRequest`.
