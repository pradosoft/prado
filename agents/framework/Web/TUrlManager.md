# Web/TUrlManager

### Directories
[framework](../INDEX.md) / [Web](./INDEX.md) / **`TUrlManager`**

## Class Info
**Location:** `framework/Web/TUrlManager.php`
**Namespace:** `Prado\Web`

## Overview
TUrlManager is the base class for managing URLs that can be recognized by PRADO applications. It provides the default implementation for parsing and constructing URLs.

## Key Features
- URL construction for both GET and PATH format URLs
- URL parsing for both GET and PATH format URLs  
- Support for HiddenPath URL format with Apache rewrite rules
- Integration with THttpRequest for request handling
- Extensible through inheritance for custom URL schemes
- Compatible with PRADO's application module system

## URL Formats Supported
1. **GET Format**: `/entryscript.php?serviceID=serviceParameter&get1=value1&get2=value2`
2. **PATH Format**: `/entryscript.php/serviceID,serviceParameter/get1,value1/get2,value2`  
3. **HIDDENPATH Format**: `/serviceID,serviceParameter/get1,value1/get2,value2` (requires Apache rewrite configuration)

## Core Methods

### URL Construction
- `constructUrl($serviceID, $serviceParam, $getItems, $encodeAmpersand, $encodeGetItems)`: 
  - Constructs a URL recognizable by PRADO based on configured URL format
  - Supports three URL formats: Get, Path, and HiddenPath
  - Handles GET parameter encoding and special array parameter handling
  - Uses configured separator for PATH format parameters (comma by default)

### URL Parsing
- `parseUrl()`: 
  - Parses the request URL and returns input parameters as an array
  - Handles both GET and PATH format URLs
  - Supports array parameters using [] notation
  - Decodes URL-encoded path information
  - Parses PATH format parameters using configured separator

## Integration with [THttpRequest](./THttpRequest.md)
- Used by [THttpRequest](./THttpRequest.md) for URL construction and parsing via `getUrlManagerModule()` method
- Configured through [THttpRequest](./THttpRequest.md)'s `UrlManager` property
- Works with [THttpRequest](./THttpRequest.md)'s `UrlFormat` to determine which format to use
- Supports [THttpRequest](./THttpRequest.md)'s `UrlParamSeparator` for PATH format parameter separation

## Configuration
### URL Format Settings
- `[THttpRequestUrlFormat](./THttpRequestUrlFormat.md)::Get`: Standard query string URL format
- `[THttpRequestUrlFormat](./THttpRequestUrlFormat.md)::Path`: Path-based URL format (e.g., /controller/action/param1,value1)
- `[THttpRequestUrlFormat](./THttpRequestUrlFormat.md)::HiddenPath`: Hidden path format without entry script (requires .htaccess rewrite rules)

### Apache Rewrite Example for HiddenPath
```apache
Options +FollowSymLinks
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php/$1 [L]
```

## Customization
To implement custom URL schemes:
1. Extend TUrlManager class
2. Override `constructUrl()` for custom URL formatting
3. Override `parseUrl()` for custom URL parsing
4. Register custom manager as an application module
5. Set [THttpRequest](./THttpRequest.md)'s UrlManager property to module ID

## Usage Example
```php
// Default URL construction
$url = $request->constructUrl('page', 'home', ['key' => 'value']);

// Custom manager usage
$customManager = $app->getModule('customUrlManager');
$request->setUrlManager('customUrlManager');
$url = $request->constructUrl('page', 'home', ['key' => 'value']);
```