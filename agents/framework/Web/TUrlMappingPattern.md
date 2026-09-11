# Web/TUrlMappingPattern

### Directories
[framework](../INDEX.md) / [Web](./INDEX.md) / **`TUrlMappingPattern`**

## Class Info
**Location:** `framework/Web/TUrlMappingPattern.php`
**Namespace:** `Prado\Web`

## Overview
TUrlMappingPattern represents a pattern used to parse and construct URLs in PRADO's URL mapping system. It defines how URLs should be matched against request paths and how URLs should be constructed from parameters.

## Key Features
- Pattern-based URL matching with parameter extraction
- Query string matching, either as part of the pattern or as per-variable constraints
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
- `Parameters` ([TAttributeCollection](../Collections/TAttributeCollection.md)): Collection of parameter validation patterns (regex)
- `Constants` ([TAttributeCollection](../Collections/TAttributeCollection.md)): Collection of constant parameters (fixed values)
- `Query` ([TAttributeCollection](../Collections/TAttributeCollection.md)): Collection of GET variable validation patterns (regex), since 4.4.0
- `CaseSensitive` (bool): Whether pattern matching is case sensitive, defaults to true

### Match Restriction
- `UrlMatchMode` ([TUrlMappingPatternUrlMatchMode](./TUrlMappingPatternUrlMatchMode.md)): The part of the URL the pattern matches, `PathInfo` (default) or `Full`, since 4.4.0
- `Verbs` (null|array|string): HTTP methods the pattern matches, comma separated, null (default) matches any, since 4.3.3

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

## Query String Matching

Two ways match the query string of a request. Both are available since 4.4.0.

| Approach | Condition | Result |
|---|---|---|
| `UrlMatchMode="Full"` | The whole PATH_INFO plus query string matches the pattern | The pattern text after the first `?` is the query string pattern |
| `query.<name>="<regex>"` | The GET variable `<name>` exists and its whole value matches the regex | The order of the GET variables in the URL does not matter |

### Full Match Mode
```xml
<url ServiceParameter="Posts.EditPost" pattern="post/{id}?mode=edit" parameters.id="\d+" UrlMatchMode="Full" />
```
- Matches `/index.php/post/123?mode=edit`
- Does not match `/index.php/post/123?mode=view`, or any other query string
- A pattern with no `?` in `Full` mode matches only a request with no query string
- `{param}` placeholders in the query string part work as they do in the path
- With `UrlFormat="Path"`, the extra path parameters stop at the question mark

### Query Constraints
```xml
<url ServiceParameter="Posts.EditPost" pattern="post/{id}" parameters.id="\d+" query.mode="edit|preview" />
```
- Matches `/index.php/post/123?mode=edit` and `/index.php/post/123?ref=list&mode=preview`
- Does not match a request with no `mode` variable, or with `mode=editor`, since the whole value must match
- A GET variable holding an array never matches a constraint
- `CaseSensitive="false"` makes the constraints case insensitive as well
- In `constructUrl()` the pattern applies only when the GET items satisfy every constraint

### Combining the Two
- The constraints are checked first, then the pattern. Both must be satisfied for the pattern to match
- `PathInfo` mode plus constraints is the order-independent combination, and leaves the pattern to the path
- `Full` mode plus constraints only narrows the match, since the pattern of a `Full` match already pins the whole query string

## Core Methods

### Pattern Matching
- `getPatternMatches($request)`: Matches URL against pattern and extracts parameters
- `getMatchSubject($request, $path)`: Returns the URL part to match, with the query string appended in `Full` mode
- `getPatternParts()`: Splits the pattern into its path part and its query string part
- `substituteParameters($pattern)`: Replaces the `{param}` placeholders of a pattern part with named groups
- `matchesQuery($items)`: Matches GET variables against the `Query` constraints
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