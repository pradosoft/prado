# TComponentProxy

### Directories
[framework](./INDEX.md) / **`TComponentProxy`**

## Class Info
**Location:** `framework/TComponentProxy.php`
**Namespace:** `Prado`
**Extends:** `TComponent`; implements [`IProxy`](./IProxy.md); uses [`TComponentProxyTrait`](./TComponentProxyTrait.md) (@since 4.4.0)

## Overview
Proxy over a `TComponent` injected through `BackingComponent`. It is not a module and has no registry to resolve from, so the backing is required before use and is kept through serialization.

```php
$proxy = new TComponentProxy();
$proxy->setBackingComponent($real);
$proxy->attachProxy();          // optional: forward the backing's on* events
$proxy->SomeProperty = 1;       // written on $real
$proxy->someMethod();           // called on $real
```

## Properties

- `BackingComponent` (`TComponent`) — the backing; `getBackingComponent()` throws `componentproxy_backing_component_required` when unset. Replacing a backing calls `detachProxy()` and logs a `TLogger::WARNING` in category `prado.component`. `attachProxy()` is not called automatically.

## Protected

- `getBackingComponentDirect()` / `setBackingComponentDirect()` — the stored reference.
- `_zappableExcludeBackingComponent(&$exprops)` — excludes the backing from serialization, for a subclass that resolves its backing lazily.

## See Also
- [TComponentProxyTrait](./TComponentProxyTrait.md) for dispatch order and event forwarding.
- [TModuleProxy](./TModuleProxy.md) for a module resolved by ID.
