# TUrlMapping Class

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [TUrlMapping](./TUrlMapping.md)

## Overview
TUrlMapping is a URL manager that allows PRADO to construct and recognize URLs based on specific patterns. It extends [TUrlManager](./TUrlManager.md) for enhanced URL handling capabilities.

## Key Features
- Pattern-based URL matching for request parsing
- Custom URL construction using defined patterns
- External configuration file support (XML or PHP)
- Wildcard pattern support for flexible matching
- Integration with [THttpRequest](./THttpRequest.md) for request handling
- Support for custom pattern classes

## Core Components
### [TUrlMappingPattern](./TUrlMappingPattern.md)
- Defines individual URL patterns for matching and construction
- Supports regular expression matching for complex URL structures
- Allows parameter specification with validation patterns
- Supports wildcard patterns for dynamic matching

### URL Format Options
- **GET Format**: Standard query string URL format
- **PATH Format**: Path-based URL format (e.g., /controller/action/param1,value1)
- **HIDDENPATH Format**: Hidden path format without entry script (requires .htaccess rewrite rules)

## Configuration
### XML Example
```xml
<module id="request" class="[THttpRequest](./THttpRequest.md)" UrlManager="friendly-url" />
<module id="friendly-url" class="Prado\Web.TUrlMapping" EnableCustomUrl="true">
  <url ServiceParameter="Posts.ViewPost" pattern="post/{id}/" parameters.id="\d+" />
  <url ServiceParameter="Posts.ListPost" pattern="archive/{time}/" parameters.time="\d{6}" />
  <url ServiceParameter="Posts.ListPost" pattern="category/{cat}/" parameters.cat="\d+" />
</module>
```

### External Configuration File
Can load patterns from external XML or PHP files using `setConfigFile()` method.

## Core Methods

### URL Construction
- `constructUrl($serviceID, $serviceParam, $getItems, $encodeAmpersand, $encodeGetItems)`: 
  - Constructs URLs based on matching patterns when `EnableCustomUrl` is true
  - Tries to match patterns by service ID and service parameter
  - Supports wildcards using `*` placeholder
  - Falls back to parent implementation when no pattern matches

### URL Parsing
- `parseUrl()`: 
  - Parses request URL against defined patterns
  - Returns matching pattern parameters as input parameters
  - Uses first matching pattern in order of definition
  - Falls back to parent parsing when no pattern matches

### Pattern Management
- `loadUrlMappings($config)`: Loads and configures URL patterns from configuration
- `buildUrlMapping($class, $properties, $url)`: Internal method to create and configure pattern objects
- `getMatchingPattern()`: Returns the last matched pattern or null

### Configuration Properties
- `EnableCustomUrl` (bool): Whether to enable custom URL construction, defaults to false
- `ConfigFile` (string): External configuration file path, null by default  
- `DefaultMappingClass` (string): Default class for URL patterns, defaults to [TUrlMappingPattern](./TUrlMappingPattern.md)
- `UrlPrefix` (string): Prefix to be added to constructed URLs

## Pattern Matching Behavior
1. **Exact Match**: Pattern matches the entire PATH_INFO exactly
2. **Parameter Extraction**: Extracts named parameters from match groups
3. **Wildcard Support**: Supports `*` wildcard for dynamic parameters  
4. **Order Priority**: First matching pattern is used (patterns defined first take precedence)
5. **Fallback**: Falls back to parent [TUrlManager](./TUrlManager.md) implementation when no pattern matches

## Wildcard Patterns
- Support `*` in service parameter to match multiple services
- `serviceID:*` - matches all services under a service ID
- `serviceID.subservice.*` - matches sub-services with wildcard suffix

## Integration with [THttpRequest](./THttpRequest.md)
- Extends [TUrlManager](./TUrlManager.md) for full URL handling capabilities
- Registered as [THttpRequest](./THttpRequest.md) module via `setUrlManager()` method
- Works with [THttpRequest](./THttpRequest.md)'s URL format configuration
- Uses [THttpRequest](./THttpRequest.md)'s `getUrlFormat()` for format determination

## Usage Example
```php
// Define patterns in configuration
<url ServiceParameter="Posts.ViewPost" pattern="post/{id}/" parameters.id="\d+" />
<url ServiceParameter="Posts.ListPost" pattern="archive/{time}/" parameters.time="\d{6}" />

// Construct URL
$url = $request->constructUrl('page', 'Posts.ViewPost', ['id' => '123']);

// Parse URL - will match the post/{id}/ pattern
// $this->Request['id'] = '123'  
```