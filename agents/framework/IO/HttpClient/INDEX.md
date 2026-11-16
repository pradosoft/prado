# IO/HttpClient/INDEX.md

### Directories
[framework](../../INDEX.md) / [IO](../INDEX.md) / **`HttpClient`**

## Purpose

The PSR-18 HTTP client (`Prado\IO\HttpClient`). `THttpClient` sends a PSR-7 request from [`IO/Http`](../Http/INDEX.md) through an `IHttpHandler` transport and returns the response for every status. Consumers: `Prado\IO\Rest\TRestClient`, `TEtcdCache`, `TReCaptcha2`, `TPasswordStrengthValidator`, and the external `prado-rpc`. **Since 4.4.0.**

## Classes

- **`THttpClient`** — `ClientInterface`. `sendRequest()` (PSR-18, never throws for a status), `send($request, $options)`, `sendAsync()` (a `TFuture`, run in a new fiber), `request($method, $uri, $options)`, `requestAsync()`. Properties `Handler`, `Timeout` (30 s), `ConnectTimeout` (10 s), `FollowRedirects`, `MaxRedirects` (5), `Verify`, `DecodeContent`, `HttpErrors`, `Headers` (default `User-Agent: Prado/<version>`), `BaseUri`. `getHasAvailableHandler()`.
- **`THttpClientOption`** — The `$options` keys: `timeout`, `connect_timeout`, `verify`, `decode_content`, `sink`, `progress`, `token`, `allow_redirects`, `max_redirects`, `http_errors`, and for `request()` `headers`, `body`, `json`, `form_params`, `query`.
- **`IHttpHandler`** — `handle($request, $options): ResponseInterface`, `getIsAvailable()`. A handler sends exactly the request it gets and follows no redirect.
- **`IAsyncHttpHandler`** — Adds `handleAsync(): TFuture`.
- **`THttpHandler`** — Abstract base: option defaults, the http/https scheme check, request header lines, the sink, header-block parsing, progress. Extends `TApplicationComponent`, so handlers hear `fx` events.
- **`TCurlHttpHandler`** — cURL easy handle; blocks. Bodies up to `POSTFIELDS_MAX` (1 MiB) from memory, larger or unsized ones streamed (`Transfer-Encoding: chunked` when unsized). `CURLOPT_PROTOCOLS` limited to http/https; `Expect:` off; `CURLOPT_ACCEPT_ENCODING` for `decode_content`.
- **`TCurlMultiHttpHandler`** — cURL multi handle pumped by a loop timer (`PollInterval`, 10 ms; `MaxConnections`); yields in a fiber; `fxPrepareForFork` releases an idle multi handle.
- **`TCurlTransfer`** — State of one cURL transfer (request, options, handle, sink, header lines, callback error).
- **`TSocketHttpHandler`** — HTTP/1.1 over `TSocketStream::connectAsync()`, `enableCryptoAsync()`, and `TAsyncStream`; yields in a fiber. `Connection: close`; chunked request and response bodies; skips `1xx`; decodes `gzip`/`deflate` with zlib and renames `Content-Encoding`/`Content-Length` to `X-Encoded-*`. `MAX_HEADER_SIZE` 64 KiB.
- **`TStreamHttpHandler`** — PHP's `http://` wrapper (`allow_url_fopen`); blocks. Headers from `wrapper_data`; the request body is buffered; no decoding.
- **`TCachedHttpClient`** — PSR-18 decorator caching `GET`/`HEAD` 2xx responses of any `ClientInterface` in an `ICache`. Honors `Cache-Control` (`no-store`, `no-cache`, `max-age`) and `Vary` (`*` not stored); `Authorization` and `Cookie` are always in the key. `invalidate()`.
- **`THttpClientEventParameter`** — Request, options, response, exception for `OnBeforeSend`, `OnResponse`, `OnError`.
- **`THttpClientException`** — Base, `TNetworkException` + PSR-18 `ClientExceptionInterface`; `getRequest()` may be null.
- **`THttpNetworkException`** — PSR-18 `NetworkExceptionInterface`: no response (DNS, refused, timeout, broken transfer, invalid response).
- **`THttpRequestException`** — PSR-18 `RequestExceptionInterface`: unsupported scheme, no host, bad sink, too many redirects, unrewindable redirect body.
- **`THttpResponseException`** — Extends `THttpRequestException`; a 4xx/5xx thrown by `TRestClient` or `HttpErrors`; `getResponse()`, `getStatusCode()`, code = status.

## Handler choice

| Caller | `HANDLERS` / `ASYNC_HANDLERS` (first available) |
|---|---|
| Main fiber | `TCurlHttpHandler`, `TSocketHttpHandler`, `TStreamHttpHandler` |
| Any other fiber (including a Revolt callback) | `TCurlMultiHttpHandler`, `TSocketHttpHandler` |

An explicit `Handler` overrides the choice. Each handler class is created once per client, so a multi handle is shared by the client's requests.

## Redirects

`THttpClient::transfer()` follows `301 302 303 307 308` with a `Location`, resolved per RFC 3986 §5.2 (`resolveUri()`, `removeDotSegments()`). 303, and 301/302 of a POST, become GET (HEAD stays HEAD) without a body or `Content-Type`; 307/308 rewind and resend the body, or throw `httpclient_redirect_body_unrewindable`. A change of host, scheme, or port drops `Authorization` and `Cookie`. A non-http(s) `Location` is returned, not followed. `dyBeforeSend`/`dyAfterResponse` run once around the whole chain.

## Conventions

- A handler returns a response for every status and throws `THttpNetworkException` only when no complete response arrives.
- `THttpClientOption::TOKEN` cancellation throws the token's `TCancelledException`, not a client exception.
- Response bodies are written to the `sink` (default `php://temp`) and rewound; no handler returns a live socket stream.
- The tests boot PHP's built-in server through `tests/unit/IO/HttpClient/HttpServerTestTrait.php` (router `Fixtures/test-server.php`: `/echo`, `/status/{code}`, `/redirect?status=&to=`, `/redirect-loop`, `/headers`, `/cookies`, `/gzip`, `/deflate`, `/unsized`, `/large?bytes=`, `/slow?delay=`). `HttpHandlerConformanceTestCase` runs the shared cases against every handler. The server is single-threaded; a test proves concurrency by fiber interleaving, not by wall time. `TSocketHttpHandlerTest` reads canned responses over `TSocketStream::pair()` and runs https against an in-process TLS server.
