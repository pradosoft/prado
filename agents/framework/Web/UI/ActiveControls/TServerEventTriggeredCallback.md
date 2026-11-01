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
    $event = $param->getCallbackParameter();   // stdClass: ID, Event, Data
    $this->Orders->render($param->getNewWriter());
}
```

## Properties

| Property | Default | Notes |
|---|---|---|
| `Channel` | `''` | required; `TServerEventBroker::NAME_PATTERN`, else `servereventtriggeredcallback_channel_invalid` at render |
| `EventName` | `''` | the event that triggers; empty for every event of the channel (filtered on the client) |
| `ServiceID` | `events` | the `TServerEventService` ID |
| `BrokerID` | `''` | the broker module for the render-time `LastEventID`; empty finds the first |
| `ActiveControl.CausesValidation` | false | set in the constructor; a server event does not validate the user's form |

## Rendering
`<span id="ClientID" hidden="hidden"></span>`, the `serverevents` package, and `new Prado.WebUI.TServerEventTriggeredCallback({ID, EventTarget, StreamUrl, LastEventID, EventName, ...})`. `StreamUrl` is `TServerEventService::constructChannelUrl()`, with the token for the page's user. `LastEventID` is the broker's latest ID at render, so the stream starts after the events the page already shows and none published between render and connect are missed.

## Client (`serverevents.js`)
- `Prado.EventSource` keeps one `EventSource` per `StreamUrl` and opens it with `&lastEventId=<LastEventID>`; later reconnects send `Last-Event-ID`. A message `{"event", "data"}` becomes `{ID, Event, Data}` for each subscriber.
- A subscriber is inactive once deinitialized or once its anchor span leaves the document. Inactive subscribers are dropped on the next message and on each `prado:ajaxComplete`. The last one closes the `EventSource` at the end of the current task, so a control re-registered by the same callback response keeps the open stream.
- An `error` with `readyState` CLOSED (an HTTP `403`/`404`) forgets the stream; the browser does not retry it.
- `onServerEvent()` skips another `EventName`, then dispatches `Prado.CallbackRequest` with the event as `CallbackParameter`. Callbacks queue one per event, in order.

## Accessibility audit (4.4.0)
- The control renders only an empty `hidden` span: no role, focus stop, or accessible name, and nothing in the accessibility tree.
- Server-pushed changes reach the page through ordinary active control updates. A change a user must notice belongs in a live region (`role="status"`, e.g. [TActiveOutput](TActiveOutput.md) or a `TActiveLabel` inside one), as for any callback update; the control announces nothing itself.
- Updates arrive without user action. WCAG 2.2.2 applies to content that moves or auto-updates continuously: an application that pushes frequent updates offers a pause, as `TTimeTriggeredCallback` pages do.
- Focus and typing survive an update under `ActiveControl.UpdateMode="Morph"` ([TActiveUpdateMode](TActiveUpdateMode.md)).

## Gotchas
- The callback parameter comes from the browser; a handler re-reads server state and treats `Data` as user input.
- With no broker module, render throws `servereventbroker_required`.
- A burst of events queues a burst of callbacks; publish change notifications, not every row.
