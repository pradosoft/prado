# Web/UI/ActiveControls/TServerEventTriggeredCallback

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TServerEventTriggeredCallback`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TServerEventTriggeredCallback.php`
**Namespace:** `Prado\Web\UI\ActiveControls`
**Extends:** `TCallback`
**Uses:** `Prado\Web\Services\ServerEvents\TServerEventBrokerIDTrait`
**Since:** 4.4.0

## Overview
Sends a callback request when the server publishes an event on its `Channel`. The page holds an `EventSource` on [TServerEventService](../../Services/ServerEvents/TServerEventService.md); each event raises `OnCallback` through a regular callback, so the handler updates active controls with the page state and the callback renderer. Server push in place of [TTimeTriggeredCallback](TTimeTriggeredCallback.md) polling.

```xml
<com:TServerEventTriggeredCallback Channel="orders" EventName="changed" OnCallback="ordersChanged" />
```
```php
public function ordersChanged($sender, $param)
{
    $event = $param->getCallbackParameter();   // stdClass: Channel, ID, Event, Data
    $this->Orders->render($param->getNewWriter());
}
```

A data-only update needs no round trip; the client handler applies it and no callback is sent:
```xml
<com:TServerEventTriggeredCallback Channel="inbox" CallbackOnEvent="false"
    ClientSide.OnServerEvent="document.getElementById('unread').textContent = parameter.Data.unread;" />
```

## Properties

| Property | Default | Notes |
|---|---|---|
| `Channel` | `''` | required; `TServerEventBroker::NAME_PATTERN`, else `servereventtriggeredcallback_channel_invalid` at render |
| `EventName` | `''` | the event that triggers; empty for every event of the channel (filtered on the client) |
| `ServiceID` | `events` | the `TServerEventService` ID of the default `sse` endpoint |
| `EndpointID` | `''` | an [`IServerEventEndpoint`](../../Services/ServerEvents/IServerEventEndpoint.md) module, such as a WebSocket package's; empty for `TServerEventSseEndpoint` on `ServiceID`; else `servereventtriggeredcallback_endpointid_invalid` at render |
| `BrokerID` | `''` | the broker module for the render-time `LastEventID`; empty finds the first |
| `CallbackOnEvent` | true | false sends no callbacks; only `ClientSide.OnServerEvent` runs. Sent to the client only when false |
| `ClientSide` | [`TServerEventClientSide`](TServerEventClientSide.md) | set in the constructor; adds `OnServerEvent` to the callback options |
| `ActiveControl.CausesValidation` | false | set in the constructor; a server event does not validate the user's form |

## Rendering
`<span id="ClientID" hidden="hidden"></span>`, the `serverevents` package, the endpoint's `registerClientScripts()`, and `new Prado.WebUI.TServerEventTriggeredCallback({...endpoint options, ID, EventTarget, Channel, EventName, ...})`. The endpoint options come from `resolveEndpoint()->getClientOptions($channel, $lastEventId)`; the control's own keys win. For the default `sse` endpoint they are `Transport: 'sse'`, `StreamUrl` (`TServerEventService::constructChannelUrl()`, with the token for the page's user), and `LastEventID`. `LastEventID` is the broker's latest ID at render, so the stream starts after the events the page already shows and none published between render and connect are missed.

## Client (`serverevents.js`)
- `Prado.ServerEvents` shares one stream per transport and stream key: `registerTransport(name, {key(options), open(options, deliver, refused)})`, `subscribe(options, subscriber)` returns the key (`'<transport> <key>'`), `unsubscribe(key, subscriber)`, `streams` (`{transport, handle, subscribers}`). Options without `Transport` use `DEFAULT_TRANSPORT`, `sse`; an unregistered transport throws.
- `deliver(key, event)` dispatches the event on `document` as a `prado:serverevent` CustomEvent (`Prado.ServerEvents.DOCUMENT_EVENT`, `detail` `{Channel, ID, Event, Data}`), then hands it to each active subscriber. Any script or component can listen; at least one control on the page subscribes the stream.
- `Prado.EventSource` is the `sse` transport: one `EventSource` per `StreamUrl`, opened with `&lastEventId=<LastEventID>`; later reconnects send `Last-Event-ID`. `decode()` turns a message `{"channel", "event", "data"}` into `{Channel, ID, Event, Data}`. An `error` with `readyState` CLOSED (an HTTP `403`/`404`) is `refused()`: the stream is forgotten and not retried. The handle is `{source, url, close()}`.
- A subscriber is inactive once deinitialized or once its anchor span leaves the document. Inactive subscribers are dropped on the next event and on each `prado:ajaxComplete`. The last one closes the stream at the end of the current task, so a control re-registered by the same callback response keeps the open stream.
- `onServerEvent()` skips another `EventName`, runs `options.OnServerEvent(sender, event)` when set (a `false` return skips the callback), skips the callback when `CallbackOnEvent` is false, then dispatches `Prado.CallbackRequest` with the event as `CallbackParameter`. Callbacks queue one per event, in order.

## Accessibility audit (4.4.0)
- The control renders only an empty `hidden` span: no role, focus stop, or accessible name, and nothing in the accessibility tree.
- Server-pushed changes reach the page through ordinary active control updates. A change a user must notice belongs in a live region (`role="status"`, e.g. [TActiveOutput](TActiveOutput.md) or a `TActiveLabel` inside one), as for any callback update; the control announces nothing itself.
- Updates arrive without user action. WCAG 2.2.2 applies to content that moves or auto-updates continuously: an application that pushes frequent updates offers a pause, as `TTimeTriggeredCallback` pages do.
- Focus and typing survive an update under `ActiveControl.UpdateMode="Morph"` ([TActiveUpdateMode](TActiveUpdateMode.md)).

## Gotchas
- The callback parameter comes from the browser; a handler re-reads server state and treats `Data` as user input.
- With no broker module, render throws `servereventbroker_required`.
- A burst of events queues a burst of callbacks; publish change notifications, not every row.
- Every open page sends its own callback per event, so one event to many pages is many POSTs, each with its page state. When the event data is the whole update, use `CallbackOnEvent="false"` with `ClientSide.OnServerEvent`.
- A client-side handler's DOM change is the application's; a change a user must notice goes into a live region, as in the audit above.
