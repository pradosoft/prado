# Web/Services/ServerEvents/INDEX.md

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [Services](../INDEX.md) / **`ServerEvents`**

## Purpose

Server push for the Prado framework (namespace `Prado\Web\Services\ServerEvents`). A publisher calls `publish()` on an [`IServerEventBroker`](IServerEventBroker.md) module from any request, cron task, or daemon. [`TServerEventService`](TServerEventService.md) streams one channel of the broker to a browser `EventSource` as `text/event-stream`. [`TServerEventTriggeredCallback`](../../UI/ActiveControls/TServerEventTriggeredCallback.md) (in `Prado\Web\UI\ActiveControls`) is the page side; each event raises a regular callback of that control.

## Interfaces

- **[`IServerEventBroker`](IServerEventBroker.md)** — Carries events from a publisher to the streams: `publish()`, `receive()`, `getLastEventId()`. Each channel buffers recent events with ordered, opaque event IDs for `Last-Event-ID` resume.
- **[`IServerEventSink`](IServerEventSink.md)** — The response a stream is written to: `open()` with the headers, `write()` per chunk (false after a disconnect), `close()`.
- **[`IServerEventEndpoint`](IServerEventEndpoint.md)** — Where a browser streams a channel and the browser transport that reads it: `getClientOptions()` (`Transport` and its options) and `registerClientScripts()`. [`TServerEventSseEndpoint`](TServerEventSseEndpoint.md) is the default, `sse` over `TServerEventService`.

## Classes

- **`TServerEvent`** — The immutable event: `ID`, `Channel`, `Name`, `Data`, `Time`.
- **[`TServerEventBroker`](TServerEventBroker.md)** — Abstract base module of the brokers: name and data validation, the channel state, `BufferSize` and `EventLifetime` pruning.
- **[`TServerEventCacheBroker`](TServerEventCacheBroker.md)** — Keeps each channel in an `ICache` entry and polls it every `PollInterval`; reaches every process sharing the cache (FPM, mod_php, the built-in server). A publish holds the channel's lock in an `IMutex` (`MutexID`, else `TFileMutex`, `TDbMutex`, or `TCacheMutex` matched to the cache).
- **[`TServerEventMemoryBroker`](TServerEventMemoryBroker.md)** — Keeps each channel in the process; a `publish()` wakes the fibers waiting on the event loop. For a long-running event loop server.
- **`TServerEventBrokerIDTrait`** — The `BrokerID` property and `resolveBroker()`; an empty ID resolves the first module that implements `IServerEventBroker`. Used by the service and the control.
- **[`TServerEventStreamer`](TServerEventStreamer.md)** — Writes one channel from a broker to a sink: `retry:`, the events, and heartbeats, until `MaxLifetime` passes or the client disconnects. Holds no request state; one `run()` per stream.
- **[`TServerEventResponseSink`](TServerEventResponseSink.md)** — The PHP SAPI sink over `THttpResponse`.
- **[`TServerEventService`](TServerEventService.md)** — The PHP SAPI host of the streamer: checks the channel name and the channel token, resolves the resume point, releases the session, and runs the streamer over a `TServerEventResponseSink`.

## Other transports (WebSocket)

A package such as `belisoful/prado-websocket` carries server events over its own transport through these seams, without the SSE stream:

| Seam | Use |
|---|---|
| `fxServerEventPublished` (`TServerEventBroker::PUBLISHED_EVENT`, `announcePublished()`) | a relay module with an `fxServerEventPublished($broker, $event)` method forwards every published event; every broker raises it once the event is stored |
| `TServerEvent::toArray()` | the payload `{id, channel, event, data}` any transport sends |
| `IServerEventEndpoint` + the control's `EndpointID` | the endpoint module gives the browser its `Transport` and options and registers its script |
| `Prado.ServerEvents.registerTransport(name, {key, open})` | the browser transport; it calls `deliver({Channel, ID, Event, Data})`, so the `prado:serverevent` document event, `OnServerEvent`, and callbacks work unchanged |
| `createChannelToken()` / `verifyChannelToken()` with `$userName` | the transport's server checks the channel token for the user it authenticated outside a PRADO request |

## Hosts

| Host | Entry | Sink | Broker |
|---|---|---|---|
| PHP SAPI request | `TServerEventService` | `TServerEventResponseSink` | `TServerEventCacheBroker` |
| event loop server, such as an HTTP/2 server | the host's request handler with `TServerEventBroker::isValidName()` and `TServerEventService::verifyChannelToken()` | its own `IServerEventSink` | `TServerEventMemoryBroker` or `TServerEventCacheBroker` |

## Conventions

- **Names** — Channel and event names match `TServerEventBroker::NAME_PATTERN`: 1 to 128 letters, digits, `_`, `.`, `:`, or `-`.
- **Channel token** — The stream URL carries an HMAC of the channel and the user name (`TServerEventService::constructChannelUrl()`); the service checks it instead of a CSRF token.
- **Errors** — Message keys start with `servereventbroker_`, `servereventcachebroker_`, and `servereventstreamer_` in `framework/Exceptions/messages/messages.txt`.
- **Tests** — `tests/unit/Web/Services/ServerEvents/`; the functional harness is `tests/harness/server-events/`.
