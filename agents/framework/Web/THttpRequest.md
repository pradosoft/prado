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
- Reads and decodes the request body once by media type (JSON, form-encoded, multipart) (@since 4.4.0)
- Implements PHP session integration for cookie-only handling

## Core Properties
- `UrlManager` ([TUrlManager](./TUrlManager.md)): The URL manager module for URL handling
- `UrlFormat` ([THttpRequestUrlFormat](./THttpRequestUrlFormat.md)): Format of URLs (Get or Path), defaults to Get
- `ResolveMethod` ([THttpRequestResolveMethod](./THttpRequestResolveMethod.md)): Method to determine service resolution (ParameterOrder or ServiceOrder)
- `UrlParamSeparator` (string): Separator used for PATH format URL parameters, defaults to comma ','
- `ServiceID` (string): Requested service ID
- `ServiceParameter` (string): Requested service parameter
- `EnableCookieValidation` (bool): Whether cookies should be validated, defaults to false; cookies are signed with `TSecurityManager::CookieValidationKey`
- `EnableCsrfCookie` (bool): Whether the CSRF secret is kept in the `PRADO_CSRF` cookie, signed with `CookieValidationKey`, HttpOnly and Secure over HTTPS; defaults to true. False keeps it in the session, which must exist (`httprequest_csrf_session_required`) (@since 4.4.0)
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
- `getContentType($mimetypeOnly = true)`: Returns the request content type; by default only the mimetype, without the `;` parameters
- `getIsSecureConnection()`: Returns whether request is sent via secure channel (HTTPS)
- `getPathInfo()`: Returns path part of request URL
- `getQueryString()`: Returns query string part of request URL  
- `getHeaders($case = null)`: Returns HTTP request headers, cached per module instance
- `getHeader($name, $default = null)`: Returns one request header, matched without regard to case (@since 4.4.0)
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
- `getBrowser()`: Returns PHP browser capabilities and throws `TConfigurationException` when the `browscap` directive is not configured

### Request Body (@since 4.4.0)
- `getRawBody()`: The raw request body from `php://input`, read once through the protected seam `readRawBody()` and cached; `getRawBodyDirect()` / `setRawBodyDirect(?string)` back the cache, so a subclass or test supplies a body by overriding the seam or setting the cache
- `getParsedBody()`: The body decoded by the media type of `getContentType()`, parsed once through the protected `parseBody()` and cached behind `getParsedBodyDirect()` / `setParsedBodyDirect(?array)`:
  - `application/json` or `application/*+json` (`TMediaType::matchesPattern()`) → `json_decode()`; an empty body or a scalar gives `[]`; malformed JSON throws `THttpException` 400 (`httprequest_body_json_invalid`)
  - `application/x-www-form-urlencoded` → `$_POST` for POST; `parse_str()` of the raw body for PUT, PATCH and the other methods, which PHP does not fill `$_POST` for
  - `multipart/form-data` → `$_POST`, which PHP fills for POST only
  - no content type or any other type → `[]`
- `getQueryParameters()`: The query-string variables alone (`$_GET`); `itemAt()` merges the URL manager route parameters, `$_GET` and `$_POST`
- Services read the body through these methods instead of `php://input`, `$_POST` or `$_GET`; `TCspReportingService::readBody()` delegates to `getRawBody()`

### CSRF Token (@since 4.4.0)
- `getCsrfToken(bool $regenerate = false)`: The token for forms and scripts of this response: the secret masked with random bytes, so every call differs and a compressed page does not expose the secret. A request without a secret receives one, written to the cookie or session `CSRF_TOKEN_KEY` (`PRADO_CSRF`). `$regenerate` replaces the secret; `TAuthManager::login()` and `logout()` do this
- `validateCsrfToken(?string $token = null)`: Compares a token with the secret using `hash_equals()`. Without an argument, reads the `CSRF_TOKEN_FIELD` (`PRADO_CSRF_TOKEN`) POST field, then the `CSRF_TOKEN_HEADER` (`X-Prado-Csrf-Token`) header. The query string never supplies the token
- Protected seams: `getCsrfTokenFromRequest()`, `loadCsrfToken()`, `saveCsrfToken()`, `createCsrfCookie()`, `getCsrfSession()`, `generateCsrfToken()` (32 hex characters), `maskCsrfToken()` / `unmaskCsrfToken()` (base64 of mask + XOR)
- Enforcement: [TPage](UI/TPage.md) `EnableCsrfValidation` (on by default) checks every postback and callback; [TService](../TService.md) `EnableCsrfValidation` (off by default) checks state-changing requests to the other services

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
- Uses [TEventResults](../TEventResults.md) with EVENT_REVERSE priority for stacking event results

## Integration with Other Components
- Registered with TApplication as request module via `getApplication()->setRequest()`
- Works with [TUrlManager](./TUrlManager.md) for URL interpretation and construction  
- Integrates with [THttpCookieCollection](./THttpCookieCollection.md) for cookie handling
- Uses [THttpCookie](./THttpCookie.md) for individual cookie objects
- Interacts with [TSecurityManager](../Security/TSecurityManager.md) for cookie validation when enabled, and to sign the CSRF cookie

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
