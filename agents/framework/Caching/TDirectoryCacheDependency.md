# Caching/TDirectoryCacheDependency

### Directories
[framework](../INDEX.md) / [Caching](./INDEX.md) / **`TDirectoryCacheDependency`**

## Class Info
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

The constructor argument is optional. A dependency created without it, as `Prado::createComponent()` does for a component configured by attributes, is given its directory through `Directory`:

```php
$dependency = Prado::createComponent(TDirectoryCacheDependency::class);
$dependency->Directory = '/path/to/templates';
```

## Properties

- `Directory` - Path to directory to watch; `''` before it is set. Setting it resolves the path, throws `TInvalidDataValueException` for a path that is not a directory, and records the snapshot of file modification times.
- `RecursiveCheck` - Whether to check subdirectories (default: true)
- `RecursiveLevel` - Depth limit (-1 = unlimited, 0 = top-level only)

## Behavior

- `getHasChanged()` scans the directory again and compares the result with the snapshot. A changed modification time, an added file, and a removed file are each a change.
- `getHasChanged()` returns `false` before `Directory` is set. `RecursiveCheck` and `RecursiveLevel` may be set before `Directory`.
- `generateTimestamps()` calls `clearstatcache(true, $path)` for each entry before it reads the entry, and `setDirectory()` does the same for the directory. A long-lived process (a cron daemon, a worker) polling the dependency sees a change made by another process. PHP otherwise answers a repeated read of the same path from its stat cache, which hides a change in a directory holding one file.

## Extension Points

Override `validateFile($fileName)` or `validateDirectory($directory)` to selectively include/exclude files.

## See Also

- [TCache](./TCache.md) for full caching documentation