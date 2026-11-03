# Web/Services/ServerEvents/IServerEventBroker

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [Services](../INDEX.md) / [ServerEvents](./INDEX.md) / **`IServerEventBroker`**

## Class Info
**Location:** `framework/Web/Services/ServerEvents/IServerEventBroker.php`
**Namespace:** `Prado\Web\Services\ServerEvents`
**Since:** 4.4.0

## Overview
Carries server events from a publisher to the streams of [`TServerEventService`](TServerEventService.md). Each channel buffers recent events with ordered, opaque event IDs, which lets a reconnecting `EventSource` resume from `Last-Event-ID`.

| Method | Description |
|---|---|
| `publish(string $channel, string $event, mixed $data = null): TServerEvent` | Buffers the event and returns it with its ID. |
| `getLastEventId(string $channel): string` | The latest event ID; `''` when nothing is buffered. |
| `receive(string $channel, string $lastEventId, float $timeout): array` | The `TServerEvent`s after the ID, waiting up to `$timeout` seconds for one. `''` or an unknown ID selects every buffered event. |

## Implementations

| Class | Waits by | Reaches |
|---|---|---|
| [`TServerEventCacheBroker`](TServerEventCacheBroker.md) | polling an `ICache` every `PollInterval` | every process sharing the cache (FPM, mod_php, built-in server) |
| [`TServerEventMemoryBroker`](TServerEventMemoryBroker.md) | suspending on the `TAsync` loop; a publish wakes it | the streams of its own process |

Both extend [`TServerEventBroker`](TServerEventBroker.md). `TServerEvent` is the immutable event: `ID`, `Channel`, `Name`, `Data`, `Time`. `TServerEventBrokerIDTrait` gives the service and the control the `BrokerID` property and `resolveBroker()` (empty ID: the first module that implements the interface; errors `servereventbroker_id_invalid`, `servereventbroker_required`).

A custom broker (Redis pub/sub, a database table, a message queue) implements the interface, or extends `TServerEventBroker` and stores its state array.
