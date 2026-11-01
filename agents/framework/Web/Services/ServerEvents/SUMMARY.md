# Web/Services/ServerEvents/SUMMARY.md

Server push (namespace `Prado\Web\Services\ServerEvents`): brokers carry published events to `text/event-stream` streams of a browser `EventSource`; the page side is `TServerEventTriggeredCallback`.

## Classes

- **`IServerEventBroker`** — Interface: `publish()`, `receive()`, `getLastEventId()`; per-channel buffer with ordered event IDs.

- **`IServerEventSink`** — Interface: the response a stream is written to; `open()`, `write()`, `close()`.

- **`IServerEventEndpoint`** — Interface: where the browser streams a channel and its transport; `getClientOptions()`, `registerClientScripts()`.

- **`TServerEvent`** — Immutable event: `ID`, `Channel`, `Name`, `Data`, `Time`; `toArray()` is the transport-neutral payload.

- **`TServerEventBroker`** — Abstract base broker module; name and data validation, `BufferSize`, `EventLifetime`; raises the global `fxServerEventPublished` after a publish (`announcePublished()`).

- **`TServerEventSseEndpoint`** — The default endpoint: `sse` over `TServerEventService`.

- **`TServerEventCacheBroker`** — Broker over an `ICache` entry per channel, polled every `PollInterval`; across processes. Publishes take the channel's lock in an `IMutex` (`MutexID` or a default matched to the cache).

- **`TServerEventMemoryBroker`** — In-process broker; `publish()` wakes fibers waiting on the event loop.

- **`TServerEventBrokerIDTrait`** — `BrokerID` property and `resolveBroker()`.

- **`TServerEventStreamer`** — Writes one channel from a broker to a sink: `retry:`, events, heartbeats, until `MaxLifetime`.

- **`TServerEventResponseSink`** — PHP SAPI sink over `THttpResponse`.

- **`TServerEventService`** — PHP SAPI service; checks the channel token, releases the session, runs the streamer.
