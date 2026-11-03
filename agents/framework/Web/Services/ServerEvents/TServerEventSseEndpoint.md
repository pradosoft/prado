# Web/Services/ServerEvents/TServerEventSseEndpoint

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [Services](../INDEX.md) / [ServerEvents](./INDEX.md) / **`TServerEventSseEndpoint`**

## Class Info
**Location:** `framework/Web/Services/ServerEvents/TServerEventSseEndpoint.php`
**Namespace:** `Prado\Web\Services\ServerEvents`
**Extends:** `Prado\TModule`
**Implements:** [`IServerEventEndpoint`](IServerEventEndpoint.md)
**Since:** 4.4.0

## Overview
The server-sent event endpoint: [`TServerEventService`](TServerEventService.md) `ServiceID` (default `events`), read by the browser's `EventSource` (`sse` transport). The default endpoint of `TServerEventTriggeredCallback`, created from its `ServiceID` when `EndpointID` is empty; configurable as a module and named by `EndpointID`.

| Member | Description |
|---|---|
| `TRANSPORT` | `'sse'` |
| `ServiceID` | the service ID; `setServiceID()` returns the endpoint |
| `getClientOptions($channel, $lastEventId)` | `{Transport: 'sse', StreamUrl: constructChannelUrl($channel, ServiceID), LastEventID}` |
| `registerClientScripts($page)` | nothing: `sse` is in the `serverevents` package |
