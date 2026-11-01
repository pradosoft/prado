# Web/Services/ServerEvents/TServerEventBroker

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [Services](../INDEX.md) / [ServerEvents](./INDEX.md) / **`TServerEventBroker`**

## Class Info
**Location:** `framework/Web/Services/ServerEvents/TServerEventBroker.php`
**Namespace:** `Prado\Web\Services\ServerEvents`
**Extends:** `Prado\TModule`
**Implements:** [`IServerEventBroker`](IServerEventBroker.md)
**Uses:** `TApplicationClockAwareTrait`
**Since:** 4.4.0

## Overview
The abstract base of the brokers. `publish()` validates the names and the data, then calls the subclass's `storeEvent()`. `getLastEventId()` reads the subclass's `loadState()`.

## State
A channel's state is `['generation' => 8 hex chars, 'sequence' => int, 'events' => [['sequence', 'name', 'data', 'time'], ...]]`. Helpers: `createState()`, `appendEvent($state, ...)` (returns `[$state, TServerEvent]`), `pruneState()`, `eventsAfter($state, $channel, $lastEventId)`, `formatEventId()`.

- Event ID: `<generation>-<sequence>`. A new state has a new random generation, so an ID from before a cache flush or expiry selects every buffered event instead of none.
- Pruning keeps at most `BufferSize` events (default 100), each for at most `EventLifetime` seconds (default 300), by the application clock.

## Validation

| Input | Rule | Error |
|---|---|---|
| channel, event name | `NAME_PATTERN` `/^[A-Za-z0-9_.:\-]{1,128}$/`; `isValidName()` | `servereventbroker_name_invalid` |
| data | `json_encode()` succeeds | `servereventbroker_data_invalid` |
| `BufferSize`, `EventLifetime` | >= 1 | `servereventbroker_buffersize_invalid`, `servereventbroker_eventlifetime_invalid` |

## Gotchas
- A browser that reconnects after its last event left the buffer misses the events in between; a handler that re-reads server state on the next event recovers.
