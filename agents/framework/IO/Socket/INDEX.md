# IO/Socket/INDEX.md

### Directories
[framework](../../INDEX.md) / [IO](../INDEX.md) / **`Socket`**

## Purpose

Socket streams and servers over PHP's stream transports (TCP, UDP, UNIX, UDG, with TLS and ALPN), the dependency-free `select()` event loop, and the module and shell action that run a socket server from the application or the CLI.

## Classes

- **`TSocketStream`** — A connected or datagram socket `TStream`: `connect()`, `bind()`, `pair()`, `recvFrom()`/`sendTo()`, local and remote `TSocketAddress`, TLS negotiation. `connectAsync()` and `enableCryptoAsync()` return a `TFuture` and wait on `TAsync::getLoop()`.
- **`TSocketServer`** — A listening socket: `bind()`, `accept()`, the connection registry, and the static `select()` over `IResource` objects or raw resources that every loop wait goes through.
- **`TSocketAddress`** — A parsed socket address (host, port, path, family).
- **[`TSocketReactor`](./TSocketReactor.md)** — The `select()` event loop: an [`IEventLoop`](../Async/IEventLoop.md) and [`ITickableEventLoop`](../Async/ITickableEventLoop.md) with the `register()` source API for servers.
- **`TSocketServerModule`** — Runs a `TSocketServer` as a daemon on an `IEventLoop` (`EventLoop`, `EventLoopClass`, default `TRevoltEventLoop`); raises `onClientConnect`, `onClientData`, `onClientDisconnect`. Seams: `createServer()`, `createEventLoop()`, `serveLoop()`, `acceptClient()`, `dispatchClient()`, `forgetConnection()`. `stop()` cancels the accept callback, closes the server, and stops the loop while serving. `serve()` installs `EventLoop` as the `TAsync` loop while serving and restores the prior one on exit.
- **`TSocketServerAction`** — The `socket/serve` shell action paired with the module.

## Notes

- `TSocketServerModule::serveLoop()` registers the listener with `onReadable()`, a `repeat(SERVE_PUMP_INTERVAL)` timer that delivers sync-mode signals and stops the loop once the server no longer listens, and runs the loop; the `finally` cancels every callback it registered.
- A handler that starts a fiber with `TAsync::run()` waits on `TAsync::getLoop()`. `serve()` makes that the served loop, so a fiber suspended on a `TSocketReactor` resumes. A `serveLoop()` override that runs a loop other than `EventLoop` sets `TAsync::setLoop()` to it, or its handlers' fibers never resume.
- A connection closed by an `onClientData` handler is forgotten by `dispatchClient()`, so a closed resource never reaches the next `select()`.

## Non-blocking connect and TLS

| Method | Waits on the loop for | Resolves with |
|---|---|---|
| `TSocketStream::connectAsync(string $uri, ?float $timeout = null, mixed $context = null, ?TCancellationToken $token = null): TFuture` | writability after `STREAM_CLIENT_ASYNC_CONNECT`; success when the socket has a peer | the connected socket (`static`), after `onConnect` |
| `enableCryptoAsync(bool $enable = true, ?int $method = null, ?float $timeout = null, ?TCancellationToken $token = null): TFuture` | readability while `stream_socket_enable_crypto()` returns 0 | `true` |

- Both leave the socket non-blocking; wrap it in a `TAsyncStream` for reads and writes.
- `$timeout` bounds the whole operation (null → `default_socket_timeout`). A timeout rejects with a `TSocketException` (`socketstream_connect_timeout`, `socketstream_crypto_timeout`) and closes the socket.
- A cancelled `$token` rejects with its `TCancelledException` and closes the socket. A token cancelled before the call rejects at once: no socket opens, and an existing socket is untouched.
- A refused connect rejects with a `TSocketException`: `socketstream_connect_failed` with the errno from `SO_ERROR` when ext-sockets is loaded, otherwise `socketstream_connect_no_peer`. A failed handshake rejects with `socketstream_crypto_failed` carrying the PHP/OpenSSL message, and closes the socket.
- `connectAsync()` throws `TInvalidDataValueException` (`socketstream_async_tls_scheme`) for `ssl://` and `tls://` URIs: PHP cannot finish the handshake after an asynchronous connect. Connect over `tcp://` with the `ssl` options (`peer_name`, `verify_peer`, `cafile`, `alpn_protocols`) in `$context`; `enableCryptoAsync()` applies them.
- A null `$method` with `$enable` true uses the context's `crypto_method`, or `STREAM_CRYPTO_METHOD_TLS_CLIENT` when none is set. A server-side handshake passes `STREAM_CRYPTO_METHOD_TLS_SERVER`.
- PHP does not report whether OpenSSL waits to read or to write, so the handshake retries on readability only; handshake messages fit the socket send buffer.
- Host-name resolution inside `stream_socket_client()` blocks the process; PHP has no asynchronous resolver. Pass an IP address, resolved by a non-blocking resolver such as `amphp/dns`, and set `peer_name` to the host name for certificate verification and SNI.
