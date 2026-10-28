# IO/Socket/INDEX.md

### Directories
[framework](../../INDEX.md) / [IO](../INDEX.md) / **`Socket`**

## Purpose

Socket streams and servers over PHP's stream transports (TCP, UDP, UNIX, UDG, with TLS and ALPN), the dependency-free `select()` event loop, and the module and shell action that run a socket server from the application or the CLI.

## Classes

- **`TSocketStream`** — A connected or datagram socket `TStream`: `connect()`, `bind()`, `pair()`, `recvFrom()`/`sendTo()`, local and remote `TSocketAddress`, TLS negotiation.
- **`TSocketServer`** — A listening socket: `bind()`, `accept()`, the connection registry, and the static `select()` over `IResource` objects or raw resources that every loop wait goes through.
- **`TSocketAddress`** — A parsed socket address (host, port, path, family).
- **[`TSocketReactor`](./TSocketReactor.md)** — The `select()` event loop: an [`IEventLoop`](../Async/IEventLoop.md) and [`ITickableEventLoop`](../Async/ITickableEventLoop.md) with the `register()` source API for servers.
- **`TSocketServerModule`** — Runs a `TSocketServer` as a daemon on an `IEventLoop` (`EventLoop`, `EventLoopClass`, default `TRevoltEventLoop`); raises `onClientConnect`, `onClientData`, `onClientDisconnect`. Seams: `createServer()`, `createEventLoop()`, `serveLoop()`, `acceptClient()`, `dispatchClient()`, `forgetConnection()`. `stop()` cancels the accept callback, closes the server, and stops the loop while serving.
- **`TSocketServerAction`** — The `socket/serve` shell action paired with the module.

## Notes

- `TSocketServerModule::serveLoop()` registers the listener with `onReadable()`, a `repeat(SERVE_PUMP_INTERVAL)` timer that delivers sync-mode signals and stops the loop once the server no longer listens, and runs the loop; the `finally` cancels every callback it registered.
- A connection closed by an `onClientData` handler is forgotten by `dispatchClient()`, so a closed resource never reaches the next `select()`.
