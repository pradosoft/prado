# Web/HttpHeaders/THttpHeaderRange

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / HttpHeaders / **`THttpHeaderRange`**

## Class Info
**Location:** `framework/Web/HttpHeaders/THttpHeaderRange.php`
**Namespace:** `Prado\Web\HttpHeaders`
**Extends:** `TBaseHttpHeader`
**Since:** 4.4.0

## Overview
Parses the `Range` request header (RFC 9110 §14.2) into a unit and a list of `[first, last]` pairs, and resolves the `bytes` range set to one span of a representation. It is the first request-side typed header; [THttpResponse](../THttpResponse.md)`::writeFile()` uses it for byte serving. It is not registered with `THttpHeadersManager`, which manages response headers.

## API
- `setHeaderValue($value)` — parses; `null` clears. Unit is case-insensitive and stored lowercase. OWS around each range and empty list elements are allowed. Any malformed range (non-digits, `-`, `last < first`, inner spaces) invalidates the whole value.
- `getUnit(): string`, `getRanges(): list<[?int, ?int]>`, `getIsValid(): bool`.
- `getHeaderValue(): string` — re-renders canonically, e.g. `bytes=0-499,500-,-20`; `''` when invalid.
- `resolve(int $size): array|false|null` — each pair is clamped by `resolveSpan()`, unsatisfiable pairs are dropped, and the rest are sorted and merged where they overlap or adjoin (`next.start <= end + 1`). One span → `[start, end]` inclusive; none satisfiable → `false` (→ 416); disjoint spans, invalid, or non-`bytes` → `null` (→ full 200).

| Range pair | Meaning | `resolve(1000)` |
|---|---|---|
| `[0, 499]` | bytes 0–499 | `[0, 499]` |
| `[900, 2000]` | last clamped | `[900, 999]` |
| `[500, null]` | 500 to end | `[500, 999]` |
| `[null, 100]` | final 100 bytes | `[900, 999]` |
| `[null, 0]` | suffix of zero | `false` |
| `[1000, null]` | starts past end | `false` |
| `[0, 99]`, `[100, 199]` | adjoining | `[0, 199]` |
| `[0, 9]`, `[5000, null]` | unsatisfiable one dropped | `[0, 9]` |
| `[0, 9]`, `[11, 19]` | one-byte gap, disjoint | `null` |

## Gotchas
- Digit runs too large for `int` saturate at `PHP_INT_MAX` (`toPosition()`), so a huge last position clamps and a huge first position is unsatisfiable rather than overflowing.
- An empty representation (`$size` 0) makes every range unsatisfiable.
- Disjoint ranges resolve to `null`; `multipart/byteranges` is deliberately not built (CVE-2011-3192 exposure, rare clients such as Acrobat Fast Web View). Merging needs no multipart body, so it is the supported subset.
