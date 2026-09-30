# Web/THttpResponse

### Directories
[framework](../INDEX.md) / [Web](./INDEX.md) / **`THttpResponse`**

## Class Info
**Location:** `framework/Web/THttpResponse.php`
**Namespace:** `Prado\Web`

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
- `HtmlWriterType` (string): Type of HTML writer to be used, defaults to [THtmlWriter](UI/THtmlWriter.md)

## Configuration
### XML Format
```xml
<modules>
    <module id="response" class="Prado\Web\THttpResponse" 
             CacheExpire="20" 
             CacheControl="nocache" 
             BufferOutput="true" />
</modules>
```

**PHP equivalent:**
```php
return [
    'modules' => [
        'response' => [
            'class' => 'Prado\Web\THttpResponse',
            'properties' => ['ContentType' => 'text/html'],
        ],
    ],
];
```

## Core Methods

### Output Management
- `write($str)`: Outputs a string to client (buffered or not)
- `getContents()`: Returns the content in the output buffer
- `clear()`: Clears any existing buffered content
- `flush($continueBuffering = true)`: Flushes response contents and headers
- `flushContent($continueBuffering = true)`: Internal flush implementation
- `appendFile(string $filename, bool $use_include_path = false, mixed $context = null): int|false`: Reads a file and writes it to the output buffer (@since 4.3.3)

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

### Protected Overridable Helpers (@since 4.3.3)
- `responseSetCookie(string $name, ...$args): bool` — Isolated wrapper for `setcookie()`. Override in subclasses for testing or custom cookie dispatch.
- `sessionCacheExpire(?int $value = null): int|false` — Wrapper for `session_cache_expire()`.
- `sessionCacheLimiter(?string $value = null): string|false` — Wrapper for `session_cache_limiter()`.

## Notes

- **JavaScript MIME type** — `.js` files served via `writeFile()` use MIME type `text/javascript` (the current standard; previously was `application/javascript` in some versions).

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

## Dynamic Events (@since 4.4.0)

Behaviors extend the response through dynamic events. A notification ignores the chain's result. A filter uses the result in place of its first argument. A handled flag starts as `false`, and the method skips its own work when the chain returns `true`.

| Event | Kind | Raised by | When |
|---|---|---|---|
| `dyFlushContent(bool $continueBuffering)` | notification | `flushContent()` | before any header is sent and before the buffer is flushed, so a behavior can transform the buffered body and add the headers that describe it |
| `dyWriteFile(bool $handled, string $fileName, ?string $content, string $mimeType, ?array $headers, ?bool $forceDownload, string $clientFileName, int $fileSize): bool` | handled flag | `writeFile()` | before any header is sent, with the media type, client file name, and size resolved; `true` means a behavior sent the file and `writeFile()` sends nothing |
| `dyRedirect(string $url): string` | filter | `redirect()` | before the adapter or `httpRedirect()` sends the URL, as given and before a relative URL gains the base URL; covers full-page and callback redirects |
| `dySetCookie(THttpCookie $cookie, bool $remove): THttpCookie` | filter | `addCookie()`, `removeCookie()` | before the `Set-Cookie` header and before `addCookie()` hashes the value; `$remove` is true for a deletion |

`dyWriteFile` hands a file to the web server: an `X-Sendfile` (Apache `mod_xsendfile`), `X-Accel-Redirect` (nginx), or `X-LiteSpeed-Location` behavior sends its own `Content-Type`, `Content-Disposition`, and handoff header, and the server serves the bytes, byte ranges, and conditional requests. It can also redirect to a signed CDN URL. A behavior that sends the file passes `true` along the chain (`$chain->dyWriteFile(true, …)`) so later behaviors still see the call; an observer passes the flag on unchanged and treats `true` as a response with no body to transform. With `$content` set there is no file to hand off, so a handoff behavior passes the call on. The handled-flag shape lets `TPermissionsBehavior` deny a download; the denied response is an empty 200 unless a behavior sets the status.

`dyRedirect` is the place for an app-wide redirect allowlist; it runs in `redirect()` because `TCallbackResponseAdapter::httpRedirect()` never reaches `THttpResponse::httpRedirect()`. `dySetCookie` also runs for deletions because a browser only deletes a cookie whose deletion carries the same `Path`, `Domain`, and, for `__Secure-`/`__Host-` names, `Secure`. A filter may change the cookie it receives; that cookie is the one held by `getCookies()`.

Response compression is left to the web server (`mod_deflate`, `mod_brotli`, nginx `gzip`) or to PHP's `zlib.output_compression`. Both run in C and stream.

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