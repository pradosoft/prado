# TDirectoryCacheDependency

### Directories
[./](../INDEX.md) > [Caching](./INDEX.md) > [TDirectoryCacheDependency](./TDirectoryCacheDependency.md)

**Location:** `framework/Caching/TDirectoryCacheDependency.php`
**Namespace:** `Prado\Caching`

## Overview

[TDirectoryCacheDependency](./TDirectoryCacheDependency.md) tracks the modification time of all files within a directory. The cache is invalidated when any file changes.

## Usage

```php
$dependency = new TDirectoryCacheDependency('/path/to/templates');

// Track only top-level files (no recursion)
$dependency->setRecursiveCheck(true);
$dependency->setRecursiveLevel(0);

$cache->set('key', $value, 0, $dependency);
```

## Properties

- `Directory` - Path to directory to watch
- `RecursiveCheck` - Whether to check subdirectories (default: true)
- `RecursiveLevel` - Depth limit (-1 = unlimited, 0 = top-level only)

## Extension Points

Override `validateFile($fileName)` or `validateDirectory($directory)` to selectively include/exclude files.

## See Also

- [TCache](./TCache.md) for full caching documentation