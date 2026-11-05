# Util/Clock/TApplicationClockAwareTrait

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Clock](./INDEX.md) / **`TApplicationClockAwareTrait`**

## Class Info
**Location:** `framework/Util/Clock/TApplicationClockAwareTrait.php`
**Namespace:** `Prado\Util\Clock`
**Since:** 4.4.0

## Overview
`TApplicationClockAwareTrait` extends [`TClockAwareTrait`](TClockAwareTrait.md) for request-scoped classes that share the one application "now". It overrides `getClock()` so a holder follows the application clock by default while still degrading to a local clock when no application exists (standalone use, shell tools, bootstrap-less tests).

Resolution order:

| Condition | Clock returned |
|---|---|
| An explicit clock was `setClock()`-ed | that clock (local override) |
| `Prado::getApplication()` is non-null | the application clock, read live |
| No application exists | a local [`TNativeClock`](TNativeClock.md), lazily created |

The application clock is read on **every** call rather than memoized, so a clock set on the application after the holder is constructed still applies. `setClock(null)` clears a local override and resumes following the application. A class independent of an application uses plain [`TClockAwareTrait`](TClockAwareTrait.md) instead.

```php
class MyControl extends \Prado\Web\UI\TControl
{
    use \Prado\Util\Clock\TApplicationClockAwareTrait;

    public function stamp()
    {
        return $this->getClock()->time();
    }
}
```

## Methods

| Method | Description |
|--------|-------------|
| `getClock(): IClock` | Returns the explicit local clock, else the application clock read live, else a lazily created local [`TNativeClock`](TNativeClock.md). |
| `getLocalClock(): IClock` | Protected alias of `TClockAwareTrait::getClock()`; the lazy local default used when no application is present. |

`setClock()`, `createClock()`, and `getClockClass()` are inherited unchanged from [`TClockAwareTrait`](TClockAwareTrait.md).

## Notes
- Registered in `framework/classes.php` as `TApplicationClockAwareTrait`.
- Uses `Prado::getApplication()` (static, nullable) rather than an instance `getApplication()`, so any class can use the trait, not only [`TApplicationComponent`](../../TApplicationComponent.md) descendants.
- Pairs with [`TApplication`](../../TApplication.md), which holds the shared clock via `TClockAwareTrait`; its `DEFAULT_CLOCK_CLASS` constant and `setClockClass()` config seam pick the app-wide default (e.g. [`TUTCClock`](TUTCClock.md)), while `TApplication::setClock()` injects a [`TMockClock`](TMockClock.md) that freezes every follower at once.
- For elapsed-time measurement use [`TMonotonicClock`](TMonotonicClock.md), not this wall-clock seam.
