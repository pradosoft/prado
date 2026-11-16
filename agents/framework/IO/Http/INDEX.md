# IO/Http/INDEX.md

### Directories
[framework](../../INDEX.md) / [IO](../INDEX.md) / **`Http`**

## Purpose

PSR-7 HTTP messages and the PSR-17 factory (`Prado\IO\Http`). The messages are immutable and are not tied to the client: [`THttpClient`](../HttpClient/INDEX.md) sends and returns them, and server-side code can build them. The URI is `Prado\IO\TResourceUri` and the body is a `Prado\IO\TStream`. **Since 4.4.0.**

## Classes

- **`TMessage`** — Abstract PSR-7 `MessageInterface`: protocol version, headers, body. Header names keep their first case and match case-insensitively; each header is a list of values; `getHeaderLine()` joins with `, `. Names must be RFC 9110 tokens; values reject CR, LF, and NUL and are trimmed of spaces and tabs. The body is an empty `TStream` until one is set. Protected `get*Direct`/`set*Direct`, `setHeaders()`, and `filter*()` for subclasses.
- **`TRequest`** — PSR-7 `RequestInterface`: `new TRequest($method, $uri, $headers = [], $body = null, $version = '1.1')`. The method keeps its case. `Host` comes from the URI and is the first header; `withUri($uri, true)` keeps an existing `Host`. The request target is the origin form (`/path?query`, `/` when empty) until `withRequestTarget()`.
- **`TResponse`** — PSR-7 `ResponseInterface`: `new TResponse($status = 200, $headers = [], $body = null, $version = '1.1', $reason = null)`. `REASON_PHRASES` is the IANA table, also used by `Web\THttpResponse`; `getStandardReasonPhrase()`. `isInformational()`, `isSuccess()`, `isRedirect()`, `isClientError()`, `isServerError()`.
- **`THttpFactory`** — PSR-17 request, response, URI, and stream factories in one object; extends `Prado\IO\TStreamFactory`.

## Conventions

- Every `with*()` returns a clone, or `$this` when nothing changes. A clone shares the body stream, as in Guzzle.
- Invalid input throws `\InvalidArgumentException`, as PSR-7 requires.
- A body passed to a constructor goes through `TStream::for()`: a string, a resource, or a `StreamInterface`. `null` and `''` leave the body empty.
- `TMessage::_getZappableSleepProps()` omits an unset body; a set body is a live stream, so serialize a message's parts, as `TCachedHttpClient` does, not the message.
