# IProxy

### Directories
[framework](./INDEX.md) / **`IProxy`**

## Class Info
**Location:** `framework/IProxy.php`
**Namespace:** `Prado`
**Type:** marker interface (@since 4.4.0)

## Overview
Identifies a transparent proxy without naming its concrete type. Every implementation uses [`TComponentProxyTrait`](./TComponentProxyTrait.md), so `getProxyBacking()` returns the real component:

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
