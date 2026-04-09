# TGlobalStateCacheDependency

### Directories
[./](../INDEX.md) > [Caching](./INDEX.md) > [TGlobalStateCacheDependency](./TGlobalStateCacheDependency.md)

**Location:** `framework/Caching/TGlobalStateCacheDependency.php`
**Namespace:** `Prado\Caching`

## Overview

[TGlobalStateCacheDependency](./TGlobalStateCacheDependency.md) watches a PRADO global state value. The cache is invalidated when the global state value changes.

## Usage

```php
$dependency = new TGlobalStateCacheDependency('app_version');
$cache->set('key', $value, 0, $dependency);
```

## See Also

- [TCache](./TCache.md) for full caching documentation