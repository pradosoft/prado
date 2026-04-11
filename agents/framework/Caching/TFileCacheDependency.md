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

## Properties

- `FileName` - Path to file to watch
- `Timestamp` - Current file modification time (read-only)

## See Also

- [TCache](./TCache.md) for full caching documentation