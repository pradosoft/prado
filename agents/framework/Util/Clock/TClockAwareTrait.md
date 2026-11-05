# Util/Clock/TClockAwareTrait

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Clock](./INDEX.md) / **`TClockAwareTrait`**

## Class Info
**Location:** `framework/Util/Clock/TClockAwareTrait.php`
**Namespace:** `Prado\Util\Clock`
**Since:** 4.4.0

## Overview
`TClockAwareTrait` gives a class a **`Clock` dependency** — an [`IClock`](IClock.md) read through `getClock()`/`setClock()`. When none is set, `getClock()` lazily creates one via `createClock()`, which instantiates the class named by `getClockClass()` (the real-time [`TNativeClock`](TNativeClock.md) by default). This is the inverse of [`TClockTrait`](TClockTrait.md): that trait makes a class *be* a clock; this one makes a class *hold* one. A class that is both — an `IClock` that holds one — is a [`TClockDecorator`](TClockDecorator.md). The default being the non-settable `TNativeClock` means using a controllable clock is a deliberate act.

Traits cannot declare constants, so `getClockClass()` is the overridable seam for the default class.

```php
class MyService extends \Prado\TComponent
{
    use \Prado\Util\Clock\TClockAwareTrait;

    public function doWork()
    {
        $when = $this->getClock()->now();
    }
}

// In a test, inject a deterministic clock:
$service->setClock(new \Prado\Util\Clock\TMockClock());
$service->getClock()->setNow('@1700000000');
```

## Methods

| Method | Description |
|--------|-------------|
| `getClock(): IClock` | Returns the clock, lazily creating the default via `createClock()` (memoized). |
| `setClock(?IClock $clock): static` | Injects the clock; null recreates the default on next use; returns `$this`. |
| `createClock(): IClock` | Protected. Instantiates the default via `Prado::createComponent` and validates it implements `IClock` (throws `TConfigurationException` `clockawaretrait_invalid_clock_class` otherwise). |
| `getClockClass(): string` | Protected, overridable. The default clock class (`TNativeClock`) — the trait's stand-in for a class constant. |

## Notes
- Registered in `framework/classes.php` as `TClockAwareTrait`.
- The default clock is the real-time [`TNativeClock`](TNativeClock.md); inject a [`TMockClock`](TMockClock.md) for pinnable time in tests.
- Used by [`TClockDecorator`](TClockDecorator.md), where `getClockClass()`/`createClock()` are the seams for the clock a decorator wraps by default.
