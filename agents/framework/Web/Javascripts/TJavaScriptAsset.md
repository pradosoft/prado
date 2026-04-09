# TJavaScriptAsset

### Directories

[./](../INDEX.md) > [Web](../INDEX.md) > [Javascripts](./INDEX.md) > [TJavaScriptAsset](./TJavaScriptAsset.md)

**Location:** `framework/Web/Javascripts/TJavaScriptAsset.php`
**Namespace:** `Prado\Web\Javascripts`

## Overview

TJavaScriptAsset is a utility class for passing JavaScript asset files between PRADO components. It encapsulates the URL of an asset and whether to load it asynchronously. The class renders an HTML script tag via its `__toString()` method.

## Key Properties/Methods

- `getUrl()` / `setUrl($url)` - Gets or sets the asset URL
- `getAsync()` / `setAsync($async)` - Gets or sets whether to load asynchronously
- `__toString()` - Renders the script tag (with optional async attribute)

## Usage

```php
$asset = new TJavaScriptAsset('/assets/myfile.js', true);
echo $asset; // <script async src="/assets/myfile.js"></script>
```

## See Also

- [TAssetManager](../TAssetManager.php) - Publishes and manages asset files
- [TClientScriptManager](../UI/TClientScriptManager.php) - Registers and renders script assets
