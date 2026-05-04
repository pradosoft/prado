# Caching/TChainedCacheDependency

### Directories
[framework](../INDEX.md) / [Caching](./INDEX.md) / **`TChainedCacheDependency`**

## Class Info
**Location:** `framework/Caching/TChainedCacheDependency.php`
**Namespace:** `Prado\Caching`

## Overview
[TChainedCacheDependency](./TChainedCacheDependency.md) chains multiple dependency objects. The combined dependency reports "changed" if ANY child dependency has changed (OR logic).

## Usage

```php
$chained = new TChainedCacheDependency();
$chained->getDependencies()->add(new TFileCacheDependency('/path'));
$chained->getDependencies()->add(new TGlobalStateCacheDependency('state'));

$cache->set('key', $value, 0, $chained);
```

## See Also

- [TCache](./TCache.md) for full caching documentation