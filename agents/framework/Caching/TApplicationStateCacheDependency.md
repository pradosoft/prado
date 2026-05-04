# Caching/TApplicationStateCacheDependency

### Directories
[framework](../INDEX.md) / [Caching](./INDEX.md) / **`TApplicationStateCacheDependency`**

## Class Info
**Location:** `framework/Caching/TApplicationStateCacheDependency.php`
**Namespace:** `Prado\Caching`

## Overview
[TApplicationStateCacheDependency](./TApplicationStateCacheDependency.md) invalidates cached data when the application is NOT running in Performance mode. Useful for caching data that should refresh when switching from Debug/Manual to Production modes.

## Usage

```php
$dependency = new TApplicationStateCacheDependency();
$cache->set('key', $value, 0, $dependency);
```

## See Also

- [TCache](./TCache.md) for full caching documentation