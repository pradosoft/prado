# Web/UI/TSessionPageStatePersister

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](INDEX.md) / **`TSessionPageStatePersister`**

## Class Info
**Location:** `framework/Web/UI/TSessionPageStatePersister.php`
**Namespace:** `Prado\Web\UI`
**Extends:** [`TComponent`](../../TComponent.md)
**Implements:** `IPageStatePersister`
**Since:** 3.1

## Overview
`TSessionPageStatePersister` stores page view-state in the PHP session instead of a hidden form field. A FIFO queue keyed by a unique page token keeps the most recent `HistorySize` snapshots, evicting the oldest entry when the limit is exceeded. This reduces page payload at the cost of server-side session memory.

## Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `STATE_SESSION_KEY` | `'PRADO_SESSION_PAGESTATE'` | Session key under which state snapshots are stored. |
| `QUEUE_SESSION_KEY` | `'PRADO_SESSION_STATEQUEUE'`` | Session key under which the FIFO key queue is maintained. |

## Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `Page` | [`TPage`](./TPage.md) | — | The page instance this persister works for. |
| `HistorySize` | `int` | `10` | Maximum number of page-state snapshots retained in the session. Must be ≥ 1; throws `TInvalidDataValueException` otherwise. |

## Key Methods

| Method | Description |
|--------|-------------|
| `save(mixed $state): void` | Serialises `$state`, writes it to the session under a unique key, and updates the FIFO queue, evicting the oldest snapshot when `HistorySize` is exceeded. |
| `load(): mixed` | Reads the state snapshot for the current request token from the session and returns the deserialised state. Throws `THttpException` if the state is corrupted. |

## See Also

- [`TPageStatePersister`](./TPageStatePersister.md) — default hidden-field-based persister
- `IPageStatePersister` — interface defining `save()` and `load()`
- [`TPage`](./TPage.md) — owner of the persister
