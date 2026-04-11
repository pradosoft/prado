# Util/Math/SUMMARY.md

Mathematical utility classes for exact rational number arithmetic; both classes are value objects (immutable).

## Classes

- **`TRational`** — Signed rational number (fraction) storing numerator and denominator as integers; automatically reduces to lowest terms; supports arithmetic operations and conversion to/from `float`; method: `fromFloat($value, $precision)`.

- **`TURational`** — Unsigned rational number variant of `TRational`; numerator and denominator are non-negative.
