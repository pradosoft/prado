# Util/Math/TRational

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Math](./INDEX.md) / **`TRational`**

## Class Info
**Location:** `framework/Util/Math/TRational.php`
**Namespace:** `Prado\Util\Math`

## Overview
TRational implements a rational number (fraction) with an integer numerator and denominator. Supports initialization from floats, strings (e.g., "21/13"), or arrays. Uses continued fraction algorithm to convert floats to precise fractions.

## Key Properties/Methods

- `getNumerator()` / `setNumerator($value)` - The numerator (signed 32-bit integer)
- `getDenominator()` / `setDenominator($value)` - The denominator (signed 32-bit integer)
- `getValue()` - Returns float value (numerator / denominator)
- `setValue($value, $tolerance = null)` - Set from float, string, or array
- `toArray()` - Returns [numerator, denominator]
- `__toString()` - Returns "numerator/denominator" string
- `float2rational($value, $tolerance, $unsigned)` - Static conversion using continued fractions

## Constants

- `NUMERATOR = 'numerator'`
- `DENOMINATOR = 'denominator'`
- `DEFAULT_TOLERANCE = 1.0e-6`

## See Also

- [TURational](TURational.md)
