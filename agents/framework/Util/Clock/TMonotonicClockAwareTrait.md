# Util/Clock/TMonotonicClockAwareTrait

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Clock](./INDEX.md) / **`TMonotonicClockAwareTrait`**

## Class Info
**Location:** `framework/Util/Clock/TMonotonicClockAwareTrait.php`
**Namespace:** `Prado\Util\Clock`
**Since:** 4.4.0

## Overview
Gives a class a `MonotonicClock` dependency, a [`TMonotonicClock`](TMonotonicClock.md), for durations, deadlines, timeouts, and intervals measured within one process. A wall-clock change (an NTP step, a manual change, a resumed virtual machine) does not lengthen or shorten them. Time that is stored, shared between processes, or shown stays on an [`IClock`](IClock.md) from [`TClockAwareTrait`](TClockAwareTrait.md); a class may use both traits.

| Method | Description |
|---|---|
| `getMonotonicClock(): TMonotonicClock` | the set clock, else a `TMonotonicClock` over `\hrtime()` created on first use |
| `setMonotonicClock(?TMonotonicClock): static` | sets the clock; null returns to the system timer |

```php
$deadline = $this->getMonotonicClock()->seconds() + $timeout;
while ($this->getMonotonicClock()->seconds() < $deadline) {
    $this->getClock()->sleep($interval);   // sleeping stays on IClock: it yields a fiber and a TMockClock advances
}
```

## Testing
A test sets a `TMonotonicClock` subclass whose protected `hrtime()` returns a counter it controls. A loop that waits through a mocked `IClock::sleep()` needs that counter to advance with each sleep, or the loop never reaches its deadline. Changing the mock wall clock (`setMicrotime()`) leaves the counter alone, which is how a test shows a wall-clock step does not move a timeout.
