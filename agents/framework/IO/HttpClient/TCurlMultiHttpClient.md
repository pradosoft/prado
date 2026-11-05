# IO/HttpClient/TCurlMultiHttpClient

### Directories
[framework](../../INDEX.md) / [IO](../INDEX.md) / [HttpClient](./INDEX.md) / **`TCurlMultiHttpClient`**

## Class Info
**Location:** `framework/IO/HttpClient/TCurlMultiHttpClient.php`
**Namespace:** `Prado\IO\HttpClient`
**Extends:** `TCurlHttpClient`
**Since:** 4.4.0

## Overview
Runs requests on one cURL multi handle per client, pumped from [`TAsync::getLoop()`](../Async/TAsync.md). `downloadAsync()` returns the [`TFuture`](../Async/TFuture.md) of a `THttpClientResponse`; `download()` awaits it, so a worker fiber yields while the response is in flight and the main fiber runs the loop. Several transfers proceed at once.

```php
$client = new TCurlMultiHttpClient();
$a = $client->downloadAsync('GET', $urlA);
$b = $client->downloadAsync('GET', $urlB, [], null, new TCancellationToken(5.0));
[$a, $b] = TAsync::all([$a, $b])->await();
```

## How it pumps
PHP exposes neither `CURLMOPT_SOCKETFUNCTION` nor `CURLMOPT_TIMERFUNCTION`, so the loop cannot watch libcurl's sockets. The first transfer registers a `repeat(PollInterval)` timer (default 10 ms) whose callback is `pump()`: a non-blocking `curl_multi_exec()` loop, then `curl_multi_info_read()` for each finished handle → `completeTransfer()`. `downloadAsync()` also pumps once right after adding the handle. The timer is cancelled when the last transfer completes or is cancelled, so an idle client leaves nothing on the loop.

| Member | Description |
|---|---|
| `downloadAsync($method, $url, $headers = [], $body = null, ?TCancellationToken $token = null): TFuture` | Adds a handle from `TCurlHttpClient::createHandle()`; rejects with `THttpClientException('httpclient_transport_error', $code, $message)` on a cURL error; a token cancels the transfer (handle removed, future rejected with the reason); an already cancelled token rejects before the transfer starts. |
| `download(...)` | `downloadAsync(...)->await()`. |
| `PollInterval` (float, > 0) | Seconds between pumps; applies to the next pump timer. `httpclient_poll_interval_invalid`. |
| `MaxConnections` (int, 0 = libcurl default) | `CURLMOPT_MAX_TOTAL_CONNECTIONS`, applied at once when the multi handle exists. |
| `getPendingCount(): int` | Transfers in flight. |
| `getIsAvailable()` | `function_exists('curl_multi_init')`. |

Per-request `Timeout`, `FollowRedirects`, and `MaxRedirects` come from the inherited properties through `createHandle()`.

## Forking
libcurl's resolver thread pool does not survive `fork()`; a child that destroys an inherited `CurlMultiHandle` blocks forever in `curl_multi_cleanup()` waiting for threads it does not have (seen as a forked test child that never exits, the parent waiting in `stream_select()`). The client listens to `fxPrepareForFork`, which `TProcessHelper::fork()` raises before forking: an idle client (no pending transfer) drops its multi handle in the parent and creates a new one on the next transfer. A fork while a transfer is pending is not supported; a child that inherits a live multi handle must not exit normally.

## Gotchas
- The pump timer holds the client; a client with pending transfers is not destroyed.
- Code that calls `pcntl_fork()` directly bypasses `fxPrepareForFork`; raise it on the application first, or fork before using the client.
- `download()` from the main fiber runs the loop, which also runs every other due callback (cron task fibers, timers). Code that must not re-enter the loop uses `TCurlHttpClient`.
- Not in `THttpClient::TRANSPORTS`; `THttpClient::create()` keeps returning the synchronous clients.
- A multi-handle failure (`curl_multi_exec()` returning other than `CURLM_OK`) rejects every pending transfer with the same exception.
