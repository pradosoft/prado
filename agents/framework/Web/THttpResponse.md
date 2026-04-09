# THttpResponse Class

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [THttpResponse](./THttpResponse.md)

## Overview
THttpResponse implements the mechanism for sending output to client users, managing HTTP response headers, cookies, and output buffering.

## Key Features
- Output buffering support with configurable buffer settings
- HTTP response header management including status codes 
- Cookie handling through [THttpCookieCollection](./THttpCookieCollection.md)
- File download and redirect functionality
- HTTP adapter support for custom response handling
- Charset handling for content-type headers
- HTML writer creation for output generation

## Core Properties
- `BufferOutput` (bool): Whether to enable output buffering, defaults to true
- `ContentType` (string): Sets the content type for the response, defaults to 'text/html'
- `Charset` (string/bool): Character set for output, can be false to disable
- `CacheExpire` (int): TTL for cached session pages in minutes, defaults to 180
- `CacheControl` (string): Cache control method for session pages
- `StatusCode` (int): HTTP status code, defaults to 200
- `StatusReason` (string): Reason phrase for HTTP status code
- `HtmlWriterType` (string): Type of HTML writer to be used, defaults to [THtmlWriter](../UI/WebControls/THtmlWriter.md)

## Configuration
### XML Format
```xml
<module id="response" class="Prado\Web\THttpResponse" 
         CacheExpire="20" 
         CacheControl="nocache" 
         BufferOutput="true" />
```

## Core Methods

### Output Management
- `write($str)`: Outputs a string to client (buffered or not)
- `getContents()`: Returns the content in the output buffer
- `clear()`: Clears any existing buffered content
- `flush($continueBuffering = true)`: Flushes response contents and headers
- `flushContent($continueBuffering = true)`: Internal flush implementation  

### HTTP Status and Headers
- `getStatusCode()`: Gets the current HTTP status code
- `setStatusCode($status, $reason = null)`: Sets HTTP status code with optional reason
- `getStatusReason()`: Gets the HTTP status reason phrase
- `sendHttpHeader()`: Sends the HTTP status header with the status code
- `appendHeader($value, $replace = true)`: Sends a custom header
- `getHeaders($case = null)`: Returns all current headers
- `ensureHeadersSent()`: Ensures HTTP and content-type headers are sent

### Cookie Handling
- `getCookies()`: Returns [THttpCookieCollection](./THttpCookieCollection.md) of cookies to be sent
- `addCookie($cookie)`: Sends a cookie to client
- `removeCookie($cookie)`: Deletes a cookie from client
- `setCookieValidation($value)`: Enables/disables cookie validation

### Content and File Handling
- `writeFile($fileName, $content = null, $mimeType = null)`: Sends a file to client
- `redirect($url)`: Redirects browser to specified URL
- `httpRedirect($url)`: Internal redirect implementation
- `reload()`: Reloads the current page

### HTML Writer
- `createHtmlWriter($type = null)`: Creates a new instance of HTML writer
- `createNewHtmlWriter($type, $writer)`: Internal HTML writer creation
- `getHtmlWriterType()`: Gets HTML writer type
- `setHtmlWriterType($value)`: Sets HTML writer type

### Adapter Support
- `setAdapter`([THttpResponseAdapter](./THttpResponseAdapter.md) $adapter): Sets response adapter
- `getAdapter()`: Gets response adapter
- `getHasAdapter()`: Checks if adapter exists

## HTTP Status Codes
Class supports all standard HTTP status codes defined in RFC 2616 including:
- 1xx Informational
- 2xx Success
- 3xx Redirection  
- 4xx Client Error
- 5xx Server Error

## Event Handling
- Implements `ITextWriter` interface for output writing
- Uses `appendLog()` method for error logging
- Uses internal `ensureHeadersSent()` and `ensureContentTypeHeaderSent()` to manage header sending

## Usage Examples
### Basic Output 
```php
$response = $this->getResponse();
$response->write('<h1>Hello World</h1>');
$response->flush();
```

### Redirect
```php
$response->redirect('/home');
```

### Status Code
```php
$response->setStatusCode(404, 'Not Found');
$response->write('Page not found');
$response->flush();
```

### File Download
```php
$response->writeFile('/path/to/file.pdf');
```

### Cookie Handling
```php
$cookies = $response->getCookies();
$cookie = new [THttpCookie](./THttpCookie.md)('username', 'john');
$cookie->setSecure(true);
$cookies->add($cookie);
```