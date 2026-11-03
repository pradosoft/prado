# Web/Services/ServerEvents/IServerEventEndpoint

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [Services](../INDEX.md) / [ServerEvents](./INDEX.md) / **`IServerEventEndpoint`**

## Class Info
**Location:** `framework/Web/Services/ServerEvents/IServerEventEndpoint.php`
**Namespace:** `Prado\Web\Services\ServerEvents`
**Since:** 4.4.0

## Overview
Where a browser streams a channel's server events, and the browser transport that reads it. [`TServerEventTriggeredCallback`](../../UI/ActiveControls/TServerEventTriggeredCallback.md) resolves its endpoint (`EndpointID` module, else [`TServerEventSseEndpoint`](TServerEventSseEndpoint.md) on `ServiceID`), merges the endpoint's client options under its own, and calls `registerClientScripts()` at render.

| Method | Description |
|---|---|
| `getClientOptions(string $channel, string $lastEventId): array` | the browser transport's options for the current user; `Transport` names it (`Prado.ServerEvents.registerTransport()`) |
| `registerClientScripts($page): void` | registers the transport's scripts; the control registers the `serverevents` package itself |

## Implementations

| Endpoint | Transport | Options |
|---|---|---|
| `TServerEventSseEndpoint` (default) | `sse` (`Prado.EventSource`) | `StreamUrl` (with the channel token), `LastEventID` |
| a WebSocket package's module (e.g. `belisoful/prado-websocket`) | its own, registered by its script | its own, e.g. `Url`, `Token`, `LastEventID` |

## Writing one
- Return `Transport` and what that transport's `key(options)` and `open(options, deliver, refused)` read. The control adds `ID`, `EventTarget`, `Channel`, `EventName`, and `CallbackOnEvent`, which win over the endpoint's keys.
- Authorize with `TServerEventService::createChannelToken($channel)` here and `verifyChannelToken($channel, $token, $app, $userName)` where the stream opens; the user name lets a server outside a PRADO request check the token for the user it authenticated.
- Deliver each event to the browser as `TServerEvent::toArray()` (`{id, channel, event, data}`); the browser transport maps it to `{Channel, ID, Event, Data}`.
