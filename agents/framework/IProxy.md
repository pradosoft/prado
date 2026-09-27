# IProxy

### Directories
[framework](./INDEX.md) / **`IProxy`**

## Class Info
**Location:** `framework/IProxy.php`
**Namespace:** `Prado`
**Type:** marker interface (@since 4.4.0)

## Overview
Identifies a transparent proxy without naming its concrete type and declares `getProxyBacking(): ?TComponent`, the real component. Every implementation uses [`TComponentProxyTrait`](./TComponentProxyTrait.md), which supplies the method. `TApplication::getModulesByType()` uses it to list a proxied module once.

```php
if ($module instanceof IProxy) {
    $real = $module->getProxyBacking();
}
```

## Implementations
- [`TComponentProxy`](./TComponentProxy.md) — any `TComponent`, set directly.
- [`TModuleProxy`](./TModuleProxy.md) — any module, by module ID.
- [`TCacheProxy`](./Caching/TCacheProxy.md) — a `TCache` module, by module ID.
- [`TDataSourceConfigProxy`](./Data/TDataSourceConfigProxy.md) — a `TDataSourceConfig` module, by module ID.
