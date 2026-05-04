# Caching/TCacheDependency

### Directories
[framework](../INDEX.md) / [Caching](./INDEX.md) / **`TCacheDependency`**

## Class Info
**Location:** `framework/Caching/TCacheDependency.php`
**Namespace:** `Prado\Caching`

## Overview
[TCacheDependency](./TCacheDependency.md) is the abstract base class for all cache dependency implementations. Implements [ICacheDependency](./ICacheDependency.md).

## Base Class

```php
abstract class TCacheDependency extends [TComponent](../TComponent.md) implements ICacheDependency {
    protected $Enabled = true;
    
    public function getHasChanged(): bool;
    public function setEnabled(bool $value);
}
```

## Implementations

- [TFileCacheDependency](./TFileCacheDependency.md) - File modification time
- [TDirectoryCacheDependency](./TDirectoryCacheDependency.md) - Directory contents modification
- [TGlobalStateCacheDependency](./TGlobalStateCacheDependency.md) - Application global state
- [TApplicationStateCacheDependency](./TApplicationStateCacheDependency.md) - Application mode
- [TChainedCacheDependency](./TChainedCacheDependency.md) - Chains multiple dependencies

## See Also

- [TCache](./TCache.md) for full caching documentation