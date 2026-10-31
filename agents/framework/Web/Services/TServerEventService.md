# Web/Services/TServerEventService

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [Services](./INDEX.md) / **`TServerEventService`**

## Class Info
**Location:** `framework/Web/Services/TServerEventService.php`
**Namespace:** `Prado\Web\Services`
**Extends:** `Prado\TService`
**Uses:** `TApplicationClockAwareTrait`, `TServerEventBrokerIDTrait`
**Since:** 4.4.0

## Overview
Streams the events of one channel to a browser `EventSource` as `text/event-stream`. The events come from an [`IServerEventBroker`](IServerEventBroker.md) module (`BrokerID`, else the first broker module). [`TServerEventTriggeredCallback`](../UI/ActiveControls/TServerEventTriggeredCallback.md) subscribes a page; each event it receives raises a regular callback of that control, so page updates run through the page state and the callback renderer. The stream itself carries only event names and small data.

```xml
<modules>
    <module id="cache" class="Prado\Caching\TRedisCache" />
    <module id="serverevents" class="Prado\Web\Services\TServerEventCacheBroker" />
</modules>
<services>
    <service id="events" class="Prado\Web\Services\TServerEventService" MaxLifetime="30" />
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

1. `beginStream()`: headers, `releaseSession()` (writes and closes the PHP session so its lock does not hold the user's other requests), `openOutput()` (`zlib.output_compression` off, `ignore_user_abort(true)`, `set_time_limit(MaxLifetime + 30)`, `THttpResponse::flush(false)`, every removable output buffer ended).
2. `retry: RetryInterval`.
3. Loop until `MaxLifetime` passes: `broker->receive(channel, lastId, wait)`; each event is `id: <id>\ndata: {"event":name,"data":data}\n\n`; after `HeartbeatInterval` quiet seconds a `: heartbeat` comment. `sendChunk()` writes and flushes; `isClientConnected()` false ends the loop.
4. The response ends; the browser reconnects after `retry` with `Last-Event-ID`.

## Properties

| Property | Default | Notes |
|---|---|---|
| `MaxLifetime` | 30 | seconds a stream stays open; > 0 |
| `HeartbeatInterval` | 15 | quiet seconds before `: heartbeat`; > 0 |
| `RetryInterval` | 3000 | milliseconds, the `retry:` field; >= 0 |
| `BrokerID` | `''` | the broker module; empty finds the first by type |

## Methods

| Method | Description |
|---|---|
| `static createChannelToken($channel, ?$app)` | hex `hash_hmac(HashAlgorithm, "prado-serverevent\0" . channel . "\0" . userName, ValidationKey)` |
| `static constructChannelUrl($channel, $serviceID = 'events', ?$app)` | `?events=<channel>&token=<token>`, unencoded `&` |
| `formatEvent(TServerEvent)` | the `id:`/`data:` frame |
| `authorizeChannel($channel, $token)` | `hash_equals` of the tokens; the override seam for an application check |
| `resolveLastEventId`, `beginStream`, `releaseSession`, `openOutput`, `stream`, `sendChunk`, `isClientConnected` | protected seams; tests override the output ones |

Time is read through the application clock (`TApplicationClockAwareTrait`); with a pinned `TMockClock` and a cache broker the whole stream runs without real waiting.

## Gotchas
- Under PHP-FPM or mod_php each open stream holds a worker for up to `MaxLifetime`. Size the pool for the open pages, or use `TTimeTriggeredCallback` polling.
- Over HTTP/1.1 a browser opens at most six connections per host; each channel of a page takes one.
- The PHP built-in server serves one request at a time unless `PHP_CLI_SERVER_WORKERS` is set; an open stream holds every other request until it ends.
- The token binds the channel to the user name, so logging out or in invalidates a rendered stream URL. It never expires on its own; `authorizeChannel()` is the place for revocation.
- `CSRF` is not checked: the stream is a GET that changes nothing.
