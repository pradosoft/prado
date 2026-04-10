# Util/Math/INDEX.md

### Directories
[framework](./INDEX.md) / [Util](./Util/INDEX.md) / **`Math/INDEX.md`**

## Purpose

Mathematical utility classes for exact rational number arithmetic.

## Classes

- **`TRational`** — Signed rational number (fraction). Stores numerator and denominator as integers. Automatically reduces to lowest terms. Supports arithmetic operations and conversion to/from `float`. Useful when exact fractional values are needed (e.g., EXIF metadata, unit conversions).

- **`TURational`** — Unsigned rational number variant of `TRational`. Numerator and denominator are non-negative.

## Conventions

- Both classes are value objects — treat them as immutable.
- Use [`TRational::fromFloat()`](../Util/Math/TRational.md) to approximate a float as a fraction.
- Division by zero produces a [`TInvalidDataValueException`](../Exceptions/TInvalidDataValueException.md).
