# ICacheDependency

### Directories

[./](../INDEX.md) > [Caching](./INDEX.md) > [ICacheDependency](./ICacheDependency.md)

**Location:** `framework/Caching/ICacheDependency.php`
**Namespace:** `Prado\Caching`

## Overview

`ICacheDependency` defines the contract for cache dependency objects that can invalidate cached values when underlying data changes.

## Interface Methods

```php
interface ICacheDependency {
    public function getHasChanged(): bool;
}
```

## Implementations

- [`TCacheDependency`](TCacheDependency.md) - Abstract base class
- [`TFileCacheDependency`](TFileCacheDependency.md) - File modification time
- [`TDirectoryCacheDependency`](TDirectoryCacheDependency.md) - Directory contents modification
- [`TGlobalStateCacheDependency`](TGlobalStateCacheDependency.md) - Application global state
- [`TApplicationStateCacheDependency`](TApplicationStateCacheDependency.md) - Application mode (Performance = unchanged)
- [`TChainedCacheDependency`](TChainedCacheDependency.md) - Chains multiple dependencies

## See Also

- [`TCache`](TCache.md) for full caching documentation
