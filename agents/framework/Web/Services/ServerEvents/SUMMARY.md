# Web/Services/ServerEvents/SUMMARY.md

Server push (namespace `Prado\Web\Services\ServerEvents`): brokers carry published events to `text/event-stream` streams of a browser `EventSource`; the page side is `TServerEventTriggeredCallback`.

## Classes

- **`IServerEventBroker`** — Interface: `publish()`, `receive()`, `getLastEventId()`; per-channel buffer with ordered event IDs.

- **`IServerEventSink`** — Interface: the response a stream is written to; `open()`, `write()`, `close()`.

- **`TServerEvent`** — Immutable event: `ID`, `Channel`, `Name`, `Data`, `Time`.

- **`TServerEventBroker`** — Abstract base broker module; name and data validation, `BufferSize`, `EventLifetime`.

- **`TServerEventCacheBroker`** — Broker over an `ICache` entry per channel, polled every `PollInterval`; across processes.

- **`TServerEventMemoryBroker`** — In-process broker; `publish()` wakes fibers waiting on the event loop.

- **`TServerEventBrokerIDTrait`** — `BrokerID` property and `resolveBroker()`.

- **`TServerEventStreamer`** — Writes one channel from a broker to a sink: `retry:`, events, heartbeats, until `MaxLifetime`.

- **`TServerEventResponseSink`** — PHP SAPI sink over `THttpResponse`.

- **`TServerEventService`** — PHP SAPI service; checks the channel token, releases the session, runs the streamer.
