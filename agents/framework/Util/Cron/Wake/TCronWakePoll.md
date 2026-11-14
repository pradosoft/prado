# Util/Cron/TCronWakePoll

### Directories
[framework](../../../INDEX.md) / [Util](../../INDEX.md) / [Cron](../INDEX.md) / [Wake](./INDEX.md) / **`TCronWakePoll`**

## Class Info
**Location:** `framework/Util/Cron/Wake/TCronWakePoll.php`
**Namespace:** `Prado\Util\Cron\Wake`
**Extends:** `TModule`
**Implements:** [`ICronWakeTransport`](ICronWakeTransport.md)
**Since:** 4.4.0

## Overview
`TCronWakePoll` is the abstract base of the wake transports that signal through a shared stamp and detect a wake by polling it. A signal writes a new stamp (`writeStamp()`); the waiting side compares `readStamp()` every `PollInterval` seconds with the stamp it last saw. A medium several hosts share carries the wake between hosts: a file system ([`TCronWakeFile`](TCronWakeFile.md)) or a cache ([`TCronWakeCache`](TCronWakeCache.md)).

- `wait()` sleeps in `PollInterval` slices through `TAsync::sleep()`, so a fiber yields; the remaining time is measured on the real clock from the given `$now`.
- `watch()` registers a `repeat(PollInterval)` loop callback that calls back when the stamp changes.
- `getIsAvailable()` gates every method: an unavailable medium makes `signal()` false, `wait()` null, and `watch()` false.

## Properties

| Property | Default | Description |
|---|---|---|
| `PollInterval` | `1.0` | Seconds between polls of the stamp; must be greater than zero (`cronwake_pollinterval_invalid`). |

## Subclass contract

- `readStamp(): mixed` — the current stamp, equal between two reads without a write, different after a write; null when none.
- `writeStamp(): bool` — write a new stamp.
- `getIsAvailable(): bool` — whether the medium is usable.

## See Also

- [ICronWakeTransport](ICronWakeTransport.md)
- [TCronWakeFile](TCronWakeFile.md)
- [TCronWakeCache](TCronWakeCache.md)
