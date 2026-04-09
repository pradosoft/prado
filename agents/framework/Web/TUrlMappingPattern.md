# TUrlMappingPattern Class

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [TUrlMappingPattern](./TUrlMappingPattern.md)

## Overview
TUrlMappingPattern represents a pattern used to parse and construct URLs in PRADO's URL mapping system. It defines how URLs should be matched against request paths and how URLs should be constructed from parameters.

## Key Features
- Pattern-based URL matching with parameter extraction
- Regular expression support for complex patterns
- Parameter validation using regular expressions
- Wildcard pattern support for dynamic matching
- Support for constants parameters
- Integration with [TUrlMapping](./TUrlMapping.md) for URL construction and parsing
- Secure connection handling for HTTPS/HTTP switching

## Core Properties

### Basic Configuration
- `ServiceParameter` (string): Service parameter to be matched (e.g., page class name)
- `ServiceID` (string): Service ID, defaults to 'page'
- `Pattern` (string): URL pattern to match, with parameters enclosed in braces
- `RegularExpression` (string): Full regular expression for pattern matching

### Parameter Handling
- `Parameters` ([TAttributeCollection](../../Collections/TAttributeCollection.md)): Collection of parameter validation patterns (regex)
- `Constants` ([TAttributeCollection](../../Collections/TAttributeCollection.md)): Collection of constant parameters (fixed values)
- `CaseSensitive` (bool): Whether pattern matching is case sensitive, defaults to true

### URL Construct/Parse Options
- `EnableCustomUrl` (bool): Whether to enable custom URL construction, defaults to true
- `UrlFormat` ([THttpRequestUrlFormat](./THttpRequestUrlFormat.md)): URL format (Get, Path, or HiddenPath)
- `UrlParamSeparator` (string): Separator char between parameter name and value in PATH format
- `SecureConnection` ([TUrlMappingPatternSecureConnection](./TUrlMappingPatternSecureConnection.md)): Secure connection behavior

## Pattern Syntax

### Basic Parameters
```
/pattern/{param1}/{param2}
```

### Parameter Validation
```
/pattern/{param1:\d+}/{param2:[a-zA-Z]+}
```

### Wildcard Patterns
```
/admin/{*}             // Matches any sub-path
/admin/{*}/{id:\d+}   // Matches with additional parameters
```

### Constants
```
/pattern/{param1}/constant_value
```

## Core Methods

### Pattern Matching
- `getPatternMatches($request)`: Matches URL against pattern and extracts parameters
- `supportCustomUrl($getItems)`: Determines if pattern supports URL construction with given parameters

### URL Construction
- `constructUrl($getItems, $encodeAmpersand, $encodeGetItems)`: Constructs URL using pattern
- `getParameterizedPattern()`: Converts pattern with parameters to regular expression

### Security Handling
- `applySecureConnectionPrefix($url)`: Applies HTTPS/HTTP prefix based on SecureConnection setting

## URL Format Support

### GET Format
- `/entryscript.php?serviceID=serviceParameter&param1=value1&param2=value2`

### PATH Format  
- `/entryscript.php/serviceID/serviceParameter/param1,value1/param2,value2`

### HIDDENPATH Format
- `/serviceID/serviceParameter/param1,value1/param2,value2` (requires rewrite rules)

## Secure Connection Options
- `Automatic` (default): No prefixing - use current connection type
- `Enable`: Force HTTPS prefix 
- `Disable`: Force HTTP prefix
- `EnableIfNotSecure`: Enable HTTPS if not already secure
- `DisableIfSecure`: Disable HTTPS if already secure

## Usage Example
```php
// Pattern definition
<url ServiceParameter="Posts.ViewPost" 
     pattern="post/{id}/" 
     parameters.id="\d+" />

// Matches URL: /index.php/post/123/
// Extracts: $this->Request['id'] = '123'

// URL Construction
$url = $request->constructUrl('page', 'Posts.ViewPost', ['id' => '123']);
// Results in: /index.php/post/123/
```