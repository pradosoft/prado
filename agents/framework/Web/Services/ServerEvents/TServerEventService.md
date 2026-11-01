# Web/Services/ServerEvents/TServerEventService

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [Services](../INDEX.md) / [ServerEvents](./INDEX.md) / **`TServerEventService`**

## Class Info
**Location:** `framework/Web/Services/ServerEvents/TServerEventService.php`
**Namespace:** `Prado\Web\Services\ServerEvents`
**Extends:** `Prado\TService`
**Uses:** `TServerEventBrokerIDTrait`
**Since:** 4.4.0

## Overview
Streams the events of one channel to a browser `EventSource` as `text/event-stream`. The events come from an [`IServerEventBroker`](IServerEventBroker.md) module (`BrokerID`, else the first broker module). [`TServerEventTriggeredCallback`](../../UI/ActiveControls/TServerEventTriggeredCallback.md) subscribes a page; each event it receives raises a regular callback of that control, so page updates run through the page state and the callback renderer. The stream itself carries only event names and small data.

```xml
<modules>
    <module id="cache" class="Prado\Caching\TRedisCache" />
    <module id="serverevents" class="Prado\Web\Services\ServerEvents\TServerEventCacheBroker" />
</modules>
<services>
    <service id="events" class="Prado\Web\Services\ServerEvents\TServerEventService" MaxLifetime="30" />
</services>
```
```php
$app->getModule('serverevents')->publish('orders', 'changed', ['id' => $id]);
```

## Request

| Request | Response |
|---|---|
| channel (service parameter) not matching `TServerEventBroker::NAME_PATTERN` | `404`, no body |
| `token` missing or not `createChannelToken(channel)` for the current user | `403`, no body |
| valid | `200` `text/event-stream;charset=UTF-8`, `Cache-Control: no-cache`, `X-Accel-Buffering: no` |

A browser `EventSource` does not reconnect after a `403` or `404`.

Resume point: the `Last-Event-ID` header, else the `lastEventId` query parameter (the ID the page rendered; empty means every buffered event), else the channel's latest event ID at connect time.

## Stream

The service is the PHP SAPI host of a [`TServerEventStreamer`](TServerEventStreamer.md):

1. `releaseSession()` writes and closes the PHP session so its lock does not hold the user's other requests.
2. `getStreamer()->run(createSink(), broker, channel, lastId)`. The sink is a [`TServerEventResponseSink`](TServerEventResponseSink.md) over the response with a time limit of `MaxLifetime + TIME_LIMIT_MARGIN` (30) seconds.
3. The streamer opens the sink with `TServerEventStreamer::getHeaders()`, sends `retry:`, the events, and heartbeats until `MaxLifetime` passes or the sink reports a disconnect, and closes the sink.
4. The response ends; the browser reconnects after `retry` with `Last-Event-ID`.

| Layer | PHP SAPI | Event loop host (HTTP/2 server) |
|---|---|---|
| request, channel check, token | `TServerEventService::run()` | the host's request handler, with `TServerEventBroker::isValidName()` and `verifyChannelToken()` |
| resume point | `resolveLastEventId()` | `TServerEventStreamer::resolveLastEventId($broker, $channel, $header)` |
| format, timing, loop | `TServerEventStreamer` | the same, one `run()` per stream in a fiber |
| output, disconnect | `TServerEventResponseSink` (`THttpResponse`, `connection_aborted()`) | an [`IServerEventSink`](IServerEventSink.md) over the response stream (flow-controlled write, stream close or reset) |
| waiting | `TServerEventCacheBroker` polls | `TServerEventMemoryBroker` wakes the fiber on `publish()` |

## Properties

| Property | Default | Notes |
|---|---|---|
| `MaxLifetime` | 30 | seconds a stream stays open; > 0; forwards to the streamer |
| `HeartbeatInterval` | 15 | quiet seconds before `: heartbeat`; > 0; forwards to the streamer |
| `RetryInterval` | 3000 | milliseconds, the `retry:` field; >= 0; forwards to the streamer |
| `BrokerID` | `''` | the broker module; empty finds the first by type |
| `Streamer` | read-only | the `TServerEventStreamer`, created once by `createStreamer()` |

## Methods

| Method | Description |
|---|---|
| `static createChannelToken($channel, ?$app)` | hex `hash_hmac(HashAlgorithm, "prado-serverevent\0" . channel . "\0" . userName, ValidationKey)` |
| `static verifyChannelToken($channel, $token, ?$app)` | `hash_equals` with `createChannelToken()`; false for an empty token |
| `static constructChannelUrl($channel, $serviceID = 'events', ?$app)` | `?events=<channel>&token=<token>`, unencoded `&` |
| `authorizeChannel($channel, $token)` | `verifyChannelToken()`; the override seam for an application check |
| `resolveLastEventId`, `releaseSession`, `createStreamer`, `createSink` | protected seams; tests replace the sink |

Time is read through the streamer's clock (`TApplicationClockAwareTrait`); with a pinned `TMockClock` on `getStreamer()` and a cache broker the whole stream runs without real waiting.

## Gotchas
- Under PHP-FPM or mod_php each open stream holds a worker for up to `MaxLifetime`. Size the pool for the open pages, or use `TTimeTriggeredCallback` polling.
- Over HTTP/1.1 a browser opens at most six connections per host, shared by its tabs; each channel of a page takes one. Over HTTP/2 a stream is one stream of the shared connection, limited by `SETTINGS_MAX_CONCURRENT_STREAMS`. HTTP/2 at a proxy in front of FPM still holds one worker per stream.
- The PHP built-in server serves one request at a time unless `PHP_CLI_SERVER_WORKERS` is set; an open stream holds every other request until it ends.
- The token binds the channel to the user name, so logging out or in invalidates a rendered stream URL. It never expires on its own; `authorizeChannel()` is the place for revocation.
- `CSRF` is not checked: the stream is a GET that changes nothing.
