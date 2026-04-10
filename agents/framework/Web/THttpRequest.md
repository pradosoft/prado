# Web/THttpRequest

### Directories
[framework](../INDEX.md) / [Web](./INDEX.md) / **`THttpRequest`**

## Class Info
**Location:** `framework/Web/THttpRequest.php`
**Namespace:** `Prado\Web`

## Overview
THttpRequest provides storage and access scheme for user requests sent via HTTP. It also encapsulates a uniform way to parse and construct URLs.

## Key Features
- Implements array-like access to request variables (POST and GET merged)
- Supports both GET and PATH URL formats for request handling
- Provides comprehensive HTTP request information including cookies, headers, user agents, etc.
- Handles URL parsing and construction with customizable URL managers
- Supports service-based request resolution
- Implements PHP session integration for cookie-only handling

## Core Properties
- `UrlManager` ([TUrlManager](./TUrlManager.md)): The URL manager module for URL handling
- `UrlFormat` ([THttpRequestUrlFormat](./THttpRequestUrlFormat.md)): Format of URLs (Get or Path), defaults to Get
- `ResolveMethod` ([THttpRequestResolveMethod](./THttpRequestResolveMethod.md)): Method to determine service resolution (ParameterOrder or ServiceOrder)
- `UrlParamSeparator` (string): Separator used for PATH format URL parameters, defaults to comma ','
- `ServiceID` (string): Requested service ID
- `ServiceParameter` (string): Requested service parameter
- `EnableCookieValidation` (bool): Whether cookies should be validated, defaults to false
- `CgiFix` (int): Whether to use ORIG_PATH_INFO and ORIG_SCRIPT_NAME for CGI environments

## Method Types

### Request Resolution
- `resolveRequest($serviceIDs)`: Resolves the requested service based on URL format
- `onResolveRequest($serviceIDs, $urlParams)`: Event handler for custom request resolution
- `parseUrl()`: Parses the request URL and returns input parameters

### URL Construction and Parsing
- `constructUrl($serviceID, $serviceParam, $getItems = null)`: Constructs a URL recognizable by PRADO
- `getUrlManagerModule()`: Returns the URL manager module instance
- `getUrlManager()`: Returns the URL manager module ID
- `setUrlManager($value)`: Sets the URL manager module ID

### Request Information
- `getRequestType()`: Returns request type (GET, POST, HEAD, PUT)
- `getContentType($mimetypeOnly = true)`: Returns content type of request
- `getIsSecureConnection()`: Returns whether request is sent via secure channel (HTTPS)
- `getPathInfo()`: Returns path part of request URL
- `getQueryString()`: Returns query string part of request URL  
- `getHeaders($case = null)`: Returns HTTP request headers
- `getRequestUri()`: Returns full request URI
- `getBaseUrl($forceSecureConnection = null)`: Returns base URL (schema + hostname)
- `getApplicationUrl()`: Returns entry script URL (w/o host part)
- `getAbsoluteApplicationUrl()`: Returns entry script URL (w/ host part)
- `getApplicationFilePath()`: Returns application entry script file path
- `getServerName()`: Returns server name
- `getServerPort()`: Returns server port number
- `getUrlReferrer()`: Returns URL referrer
- `getUserAgent()`: Returns user agent string
- `getUserHostAddress()`: Returns user IP address
- `getUserHost()`: Returns user host name
- `getAcceptTypes()`: Returns user browser accept types
- `getUserLanguages()`: Returns list of user preferred languages

### Cookie Handling
- `getCookies()`: Returns [THttpCookieCollection](./THttpCookieCollection.md) of cookies sent by user
- `getUploadedFiles()`: Returns list of uploaded files
- `getServerVariables()`: Returns list of server variables
- `getEnvironmentVariables()`: Returns list of environment variables

### Array-like Interface
THttpRequest implements ArrayAccess, IteratorAggregate, and Countable interfaces:
- `offsetExists($offset)`: Checks if offset exists
- `offsetGet($offset)`: Gets item at offset
- `offsetSet($offset, $item)`: Sets item at offset
- `offsetUnset($offset)`: Removes item at offset
- `getIterator()`: Returns iterator for traversing items
- `count()`: Returns number of items
- `getCount()`: Returns number of items
- `itemAt($key)`: Gets item at key
- `add($key, $value)`: Adds item
- `remove($key)`: Removes item
- `contains($key)`: Checks if key exists
- `toArray()`: Returns all items as array
- `clear()`: Clears all items
- `getKeys()`: Returns key list

## URL Management Features
- Supports two URL formats:
  1. **GET Format**: index.php?name1=value1&name2=value2  
  2. **PATH Format**: index.php/name1,value1/name2,value2
- Customizable URL manager modules through module configuration
- Caching support for URL managers to improve performance with many TUrlMappings  

## Event Handling
- `onResolveRequest`: Event for custom request resolution logic
- Uses [TEventResults](../../Util/TEventResults.md) with EVENT_REVERSE priority for stacking event results

## Integration with Other Components
- Registered with TApplication as request module via `getApplication()->setRequest()`
- Works with [TUrlManager](./TUrlManager.md) for URL interpretation and construction  
- Integrates with [THttpCookieCollection](./THttpCookieCollection.md) for cookie handling
- Uses [THttpCookie](./THttpCookie.md) for individual cookie objects
- Interacts with [TSecurityManager](../../Security/TSecurityManager.md) for cookie validation when enabled

## Usage Example
```php
// Access request parameters like array
if (isset($request['param1'])) {
    $value = $request['param1'];
}

// Get URL information
$baseUrl = $request->getBaseUrl();
$applicationUrl = $request->getApplicationUrl();
$cookies = $request->getCookies();

// Construct URLs
$url = $request->constructUrl('page', 'home', ['key' => 'value']);
```