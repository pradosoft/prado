# Caching/TFileCacheDependency

### Directories
[framework](../INDEX.md) / [Caching](./INDEX.md) / **`TFileCacheDependency`**

## Class Info
**Location:** `framework/Caching/TFileCacheDependency.php`
**Namespace:** `Prado\Caching`

## Overview
[TFileCacheDependency](./TFileCacheDependency.md) tracks a single file's modification time. The cache is invalidated when the file changes.

## Usage

```php
$dependency = new TFileCacheDependency('/path/to/config.xml');
$cache->set('key', $value, 0, $dependency);
```

The constructor argument is optional. A dependency created without it, as `Prado::createComponent()` does for a component configured by attributes, is given its file through `FileName`:

```php
$dependency = Prado::createComponent(TFileCacheDependency::class);
$dependency->FileName = '/path/to/config.xml';
```

## Properties

- `FileName` - Path to file to watch; `null` before it is set. Setting it records the file's modification time.
- `Timestamp` - Recorded file modification time (read-only); `false` when the file did not exist or no file name is set.

## Behavior

- `getHasChanged()` compares the file's current modification time with `Timestamp`. A missing file reads as `false`, so creating or deleting the file is a change.
- `getHasChanged()` returns `false` before `FileName` is set.
- `updateTimestamp()` records the current modification time as the new baseline.
- `readFileTime($fileName)` (protected) performs every modification time read. It calls `clearstatcache(true, $fileName)` first, so a long-lived process (a cron daemon, a worker) polling the dependency sees a change made by another process. PHP otherwise answers a repeated `filemtime()` of the same file from its stat cache.

## See Also

- [TCache](./TCache.md) for full caching documentation