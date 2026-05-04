# Util/Behaviors/TPageNoCacheBehavior

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Behaviors](./INDEX.md) / **`TPageNoCacheBehavior`**

## Class Info
**Location:** `framework/Util/Behaviors/TPageNoCacheBehavior.php`
**Namespace:** `Prado\Util\Behaviors`

## Overview
TPageNoCacheBehavior attaches to pages and adds no-cache meta tags to the page head. It adds Expires, Pragma, and Cache-Control HTTP meta tags with no-cache values to prevent browser caching.

## Key Properties/Methods

- `events()` - Returns `['OnInitComplete' => 'addNoCacheMeta']`
- `addNoCacheMeta($page, $param)` - Adds no-cache meta tags (Expires, Pragma, Cache-Control)
- `getCheckMetaNoCache()` / `setCheckMetaNoCache($value)` - Whether to check existing meta tags before adding (default: false)

## See Also

- [TPageGlobalizationCharsetBehavior](./TPageGlobalizationCharsetBehavior.md)
