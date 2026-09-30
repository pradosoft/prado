# Util/Log/TLogRouterProxy

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Log](./INDEX.md) / **`TLogRouterProxy`**

## Class Info
**Location:** `framework/Util/Log/TLogRouterProxy.php`
**Namespace:** `Prado\Util\Log`
**Extends:** [`TLogRouter`](./TLogRouter.md); implements `IModuleDependency`, [`IProxy`](../../IProxy.md); uses [`TComponentProxyTrait`](../../TComponentProxyTrait.md) (@since 4.4.0)

## Overview
Transparent log router module that delegates every `TLogRouter` operation to the `TLogRouter` module named by `BackingLogRouterId`. Lets the logical `log` module be swapped in configuration without touching consumers. The proxy owns no routes and attaches no `collectLogs` handler; the backing does both.

## Configuration

```xml
<modules>
    <module id="log" class="Prado\Util\Log\TLogRouterProxy" BackingLogRouterId="fileLog" />
    <module id="fileLog" class="Prado\Util\Log\TLogRouter">
        <route class="Prado\Util\Log\TFileLogRoute" Levels="Warning, Error, Fatal" />
    </module>
</modules>
```

Routes belong on the backing module. A `<route>` child, a `routes` array key, or a `ConfigFile` on the proxy throws `logrouterproxy_routes_not_allowed` at `init()`.

## Properties

- `BackingLogRouterId` (string) — required by `init()` (`logrouterproxy_backing_log_router_id_required`). Replacing a non-empty ID logs a `TLogger::WARNING` (`prado.util.log`) and drops the resolved router.
- `LogRouter` (read-only `TLogRouter`) — resolves the backing on first call; throws `logrouterproxy_log_router_not_found` or `logrouterproxy_invalid_log_router_type`, then calls `attachProxy()`.
- `ConfigFile`, `FlushCount`, `TraceLevel` — getters and setters call the backing's; `FlushCount` and `TraceLevel` reach the application `TLogger` through it.

## Behavior

- `addRoute()`, `getRoutes()`, `getRoutesCount()`, `removeRoute()`, `collectLogs()` call the backing's public methods. Every public `TLogRouter` method is overridden because `__call` never reaches an inherited method.
- `init()` validates the ID, rejects route configuration, and runs the behaviors' `dyInit` only; `TLogRouter::init()` is skipped so no route loads and the logger's `onFlushLogs` keeps one handler, the backing's.
- `getModuleDependencies()` returns the backing ID so the backing initializes first.
- Other properties, methods, and `on` events forward through `TComponentProxyTrait`; `dy` and `fx` names are not forwarded.

## Gotchas

- A `ConfigFile` set on the proxy through module properties calls the backing's setter before `init()` rejects the module; the application aborts at that point.
- The proxy's `ID` stays its own; the backing keeps its own `ID`.

## See Also
- [TLogRouter](./TLogRouter.md), [TLogger](./TLogger.md), [TModuleProxy](../../TModuleProxy.md), [TCacheProxy](../../Caching/TCacheProxy.md)
