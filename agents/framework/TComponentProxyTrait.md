# TComponentProxyTrait

### Directories
[framework](./INDEX.md) / **`TComponentProxyTrait`**

## Class Info
**Location:** `framework/TComponentProxyTrait.php`
**Namespace:** `Prado`
**Type:** trait (@since 4.4.0)

## Overview
Shared logic of every [`IProxy`](./IProxy.md) implementation. A class using the trait extends [`TComponent`](./TComponent.md) and implements `getProxyBacking(): ?TComponent`; it may override `canResolveProxyBacking(): bool` (default `false`) when the backing can be resolved lazily, such as from a module ID.

## Storage

- `$_proxyBacking` (`?TComponent`) — the resolved backing; `getProxyBackingDirect()`, `setProxyBackingDirect()`, `clearProxyBacking()`. Typed proxies wrap these in narrowing accessors (`getCacheDirect()`, `getDataSourceDirect()`).
- `$_proxyEventNames` — `lowercase event → [name, forwarder]` for the backing events wired by `attachProxy()`.

## Dispatch order

| Name | Resolves on |
|------|-------------|
| Proxy's own public getter/setter, `on*` method, or any `fx*` name | the proxy (`parent::__get()` etc.); a read-only proxy property throws on write |
| `on*` event wired by `attachProxy()` | the proxy's own handler collection |
| Property the backing `canGetProperty()`/`canSetProperty()` (its behaviors included) | the backing |
| Method the backing `hasMethod()` (its behaviors included), except `dy*`/`fx*` | the backing |
| Anything else | the proxy's behaviors through `TComponent`, else throws |

Every dispatch reads `getProxyBackingDirect()` first; `getProxyBacking()` runs only when that is `null` and `canResolveProxyBacking()` is `true`. `isa($class)` is `true` for the proxy, its behaviors, or the backing; an unresolvable backing counts as absent instead of throwing.

## Event forwarding

`attachProxy()` reflects the public `on*` events of the backing class and of each enabled backing behavior (`TComponentReflection::getEvents()`, kept when `$backing->hasEvent()` agrees). For each event it keeps a `TWeakCallableCollection` in `$this->_e[$lname]` and registers a static forwarder closure on the backing event. The forwarder captures the collection, not the proxy, and calls the proxy's handlers in priority order with the backing as `$sender`; a stopped `IEventStoppableParameter` ends the loop. The forwarder runs outside the proxy's `raiseEvent()`, so the proxy's `fx` listeners, behavior `dy` hooks, and `IEventCycleParameter` calls fire for the backing's raise only. `detachProxy()` removes the forwarders (also for an event whose behavior left the backing) and keeps the collections, so handlers survive a backing swap.

`hasEvent()` and `getEventHandlers()` recognize the wired events. `__clone()` drops the clone's backing and forwarder list without touching the original's forwarders.

## Serialization

`_addProxyEventNamesZappable()` always; `_addProxyBackingZappable()` for the ID-based proxies, which re-resolve after `unserialize()`. `TComponentProxy` keeps its backing.

## See Also
- [TComponentProxy](./TComponentProxy.md), [TModuleProxy](./TModuleProxy.md), [TCacheProxy](./Caching/TCacheProxy.md), [TDataSourceConfigProxy](./Data/TDataSourceConfigProxy.md)
