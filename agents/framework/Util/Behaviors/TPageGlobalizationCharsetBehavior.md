# Util/Behaviors/TPageGlobalizationCharsetBehavior

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Behaviors](./INDEX.md) / **`TPageGlobalizationCharsetBehavior`**

## Class Info
**Location:** `framework/Util/Behaviors/TPageGlobalizationCharsetBehavior.php`
**Namespace:** `Prado\Util\Behaviors`

## Overview
TPageGlobalizationCharsetBehavior attaches to pages and adds a charset meta tag to the page head from the globalization settings. If there is no globalization configured, 'utf-8' is used as the default charset.

## Key Properties/Methods

- `events()` - Returns `['OnInitComplete' => 'addCharsetMeta']`
- `addCharsetMeta($page, $param)` - Adds charset meta tag to page head
- `getCheckMetaCharset()` / `setCheckMetaCharset($value)` - Whether to check existing meta tags for charset before adding

## See Also

- [TPageNoCacheBehavior](./TPageNoCacheBehavior.md)
- [TGlobalization](../../I18N/TGlobalization.md)
