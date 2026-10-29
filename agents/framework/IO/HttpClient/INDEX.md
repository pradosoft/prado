# IO/HttpClient/INDEX.md

### Directories
[framework](../../INDEX.md) / [IO](../INDEX.md) / **`HttpClient`**

## Purpose

HTTP transports (`Prado\IO\HttpClient`): one request/response round trip over cURL or PHP stream wrappers, a caching wrapper, and the async cURL multi client. `Prado\IO\Rest\TRestClient` is the main consumer.

## Classes

- **`THttpClient`** — Abstract transport: `download(string $method, string $url, array $headers = [], ?string $body = null): THttpClientResponse`, properties `Timeout`, `FollowRedirects`, `MaxRedirects`. `create()` returns the first available of `TRANSPORTS` (`TCurlHttpClient`, `TFopenHttpClient`); `newCacheWrapper()` wraps one in `TCachedHttpClient`.
- **`TCurlHttpClient`** — Synchronous cURL transport. `createHandle()` builds the configured `CurlHandle` and collects response headers; `createResponse()` reads the status (both 4.4.0, shared with the multi client).
- **[`TCurlMultiHttpClient`](TCurlMultiHttpClient.md)** — Async cURL multi transport on the event loop: `downloadAsync()` returns a `TFuture`, `download()` awaits it; `PollInterval`, `MaxConnections`. Chosen explicitly, not in `TRANSPORTS`. **Since 4.4.0.**
- **`TFopenHttpClient`** — Synchronous stream-wrapper transport for `allow_url_fopen`.
- **`TCachedHttpClient`** — Caches successful `GET`/`HEAD` responses of an inner transport in an `ICache` for a TTL.
- **`THttpClientResponse`** — Status code, canonical-cased headers, body, `getJson()`, `isSuccess()`/`isRedirect()`/`isClientError()`/`isServerError()`.
- **`THttpClientException`** — Transport failure (`httpclient_transport_error`), or an HTTP error response through `fromResponse()`.
- **`TStreamDownloader`** — Streams a response body to a stream.

## Conventions

- A transport returns a `THttpClientResponse` for every HTTP status and throws `THttpClientException` only for a network-level failure.
- The tests boot PHP's built-in server through `tests/unit/IO/HttpClient/HttpServerTestTrait.php` (router `Fixtures/test-server.php`: `/echo`, `/status/{code}`, `/redirect`, `/headers`, `/large`, `/slow?delay=`). The server is single-threaded; a test proves concurrency by pending counts and fiber interleaving, not by wall time.
