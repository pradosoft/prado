# Util/Math/TURational

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Math](./INDEX.md) / **`TURational`**

## Class Info
**Location:** `framework/Util/Math/TURational.php`
**Namespace:** `Prado\Util\Math`

## Overview
TURational is an unsigned rational number implementation extending TRational. Used for EXIF and GPS IFD data which store unsigned rational values. Supports 32-bit unsigned integers on both 32-bit and 64-bit PHP.

## Key Properties/Methods

- `getIsUnsigned()` - Returns true (TURational is always unsigned)
- `setNumerator($value)` - Sets unsigned numerator (0 to 4294967295)
- `setDenominator($value)` - Sets unsigned denominator (0 to 4294967295)
- `getValue()` - Returns float value; INF is 0xFFFFFFFF/0, NAN is 0/0

## See Also

- [TRational](TRational.md)
