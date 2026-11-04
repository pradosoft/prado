# Web/UI/WebControls/TPageLoadTime

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TPageLoadTime`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TPageLoadTime.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TPageLoadTime writes the amount of time taken from the request start to rendering the contents of this control. It extends TLabel and displays the elapsed time in seconds with localizable suffix.

The time is measured on the monotonic clock ([`TMonotonicClockAwareTrait`](../../../Util/Clock/TMonotonicClockAwareTrait.md)) from [`Prado::getRequestStartNanoseconds()`](../../../Prado.md), the `$_SERVER["PRADO_REQUEST_HRTIME"]` reading taken at bootstrap, so a wall-clock change during the request does not lengthen or shorten it (4.4.0).

## Key Properties/Methods

- `getSecondSuffix()` / `setSecondSuffix()` - Gets or sets string appended to time (default 's')
- `getLoadTime(): float` - Seconds from the request start to now, on the monotonic clock (4.4.0)
- `renderContents()` - Writes `round(getLoadTime(), 5)` with the suffix

## Accessibility audit (4.4.0)
The markup is unchanged: the TLabel `<span>` with text. Only the time source changed.

## See Also

- [TLabel](./TLabel.md)
