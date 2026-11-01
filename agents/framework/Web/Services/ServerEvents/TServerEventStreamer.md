# Web/Services/ServerEvents/TServerEventStreamer

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [Services](../INDEX.md) / [ServerEvents](./INDEX.md) / **`TServerEventStreamer`**

## Class Info
**Location:** `framework/Web/Services/ServerEvents/TServerEventStreamer.php`
**Namespace:** `Prado\Web\Services\ServerEvents`
**Extends:** `Prado\TComponent`
**Uses:** `TApplicationClockAwareTrait`
**Since:** 4.4.0

## Overview
Writes the events of one channel from an [`IServerEventBroker`](IServerEventBroker.md) to an [`IServerEventSink`](IServerEventSink.md) as `text/event-stream`. It holds the format and timing and no request state, so one streamer serves any number of streams, one `run()` each, concurrently in fibers.

| Host | Caller | Sink |
|---|---|---|
| PHP SAPI (FPM, mod_php, built-in server) | [`TServerEventService`](TServerEventService.md) | [`TServerEventResponseSink`](TServerEventResponseSink.md) |
| event loop server, such as an HTTP/2 server (`belisoful/prado-http2`: `TH2Session::pump()`, `onRequest` + `TAsync::run()`) | the request handler | a sink over the response stream (`TH2Stream::writeAsync()`, `markLocalClosed()`) |

```php
$streamer = new TServerEventStreamer();
$streamer->setMaxLifetime(300);
// in the host's request handler, after TServerEventBroker::isValidName() and TServerEventService::verifyChannelToken()
$lastId = TServerEventStreamer::resolveLastEventId($broker, $channel, $lastEventIdHeader);
TAsync::run(fn () => $streamer->run($sink, $broker, $channel, $lastId));
```

## run($sink, $broker, $channel, $lastEventId): string

1. `$sink->open(getHeaders())`.
2. `retry: RetryInterval`.
3. Loop until `MaxLifetime` passes: `broker->receive(channel, lastId, wait)`, `wait` bounded by the deadline and the next heartbeat; the events of one receive go out as one chunk of `formatEvent()` frames; after `HeartbeatInterval` quiet seconds a `: heartbeat` comment. `$sink->write()` false ends the loop.
4. `$sink->close()` in a `finally`, so a broker exception still closes the sink.

Returns the ID of the last event sent, or `$lastEventId` when none was sent.

## Static Methods

| Method | Description |
|---|---|
| `getHeaders()` | `Content-Type: text/event-stream; charset=UTF-8`, `Cache-Control: no-cache`, `X-Accel-Buffering: no`. No connection-specific field (RFC 9113 §8.2.2), so HTTP/2 sends them as given. |
| `formatEvent(TServerEvent)` | `id: <id>\ndata: {"event":name,"data":data}\n\n` |
| `resolveLastEventId($broker, $channel, ?$header, ?$parameter)` | the `Last-Event-ID` header, else the rendered parameter, else `getLastEventId($channel)` |

## Properties

| Property | Default | Error code |
|---|---|---|
| `MaxLifetime` | 30 seconds, > 0 | `servereventstreamer_maxlifetime_invalid` |
| `HeartbeatInterval` | 15 seconds, > 0 | `servereventstreamer_heartbeatinterval_invalid` |
| `RetryInterval` | 3000 ms, >= 0 | `servereventstreamer_retryinterval_invalid` |

## Gotchas
- The streamer does not check the channel name or the token; the host does, before `run()`.
- In an event loop host both brokers yield to the loop: [`TServerEventMemoryBroker`](TServerEventMemoryBroker.md) wakes the fiber on a `publish()` in the same process; `TServerEventCacheBroker` polls every `PollInterval` through the clock's `sleep()` (`TAsync::sleep()`), and reaches publishers in other processes.
- A disconnect is seen at the next `write()`, at most `HeartbeatInterval` later.
