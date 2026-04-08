# Util/Math/INDEX.md - UTIL_MATH_INDEX.md

This file provides guidance to Agents when working with code in this repository.

## Purpose

Mathematical utility classes for exact rational number arithmetic.

## Classes

- **`TRational`** — Signed rational number (fraction). Stores numerator and denominator as integers. Automatically reduces to lowest terms. Supports arithmetic operations and conversion to/from `float`. Useful when exact fractional values are needed (e.g., EXIF metadata, unit conversions).

- **`TURational`** — Unsigned rational number variant of `TRational`. Numerator and denominator are non-negative.

## Conventions

- Both classes are value objects — treat them as immutable.
- Use `TRational::fromFloat($value, $precision)` to approximate a float as a fraction.
- Division by zero produces a `TInvalidDataValueException`.
