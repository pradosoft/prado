# TBitHelper

### Directories

[Util](../) > [Helpers](Helpers/) > TBitHelper

**Location:** `framework/Util/Helpers/TBitHelper.php`
**Namespace:** `Prado\Util\Helpers`

## Overview

Static utility class for bitwise and floating-point format conversions. Includes color bit shifting, float encoding/decoding (FP16, BF16, FP8), bit mirroring, and endian conversion.

## Constants

| Constant | Description |
|----------|-------------|
| `PHP_INT32_MIN`, `PHP_INT32_MAX` | 32-bit integer bounds |
| `PHP_INT64_MIN`, `PHP_INT64_MAX` | 64-bit integer bounds |
| `Level1-NLevel6`, `Mask1-Mask6` | Bit manipulation constants for O(1) operations |

## Key Methods

### Float Conversions

| Method | Description |
|--------|-------------|
| `floatToFp16(float $value)` | Encode to 16-bit half precision |
| `fp16ToFloat(int $fp16)` | Decode from 16-bit half precision |
| `floatToBf16(float $value)` | Encode to 16-bit bfloat |
| `bf16ToFloat(int $bf16)` | Decode from 16-bit bfloat |
| `floatToFp8Range(float $value)` | Encode to FP8-E5M2 |
| `fp8RangeToFloat(int $fp8)` | Decode from FP8-E5M2 |
| `floatToFp8Precision(float $value)` | Encode to FP8-E4M3 |
| `fp8PrecisionToFloat(int $fp8)` | Decode from FP8-E4M3 |

### Bit Operations

| Method | Description |
|--------|-------------|
| `bitCount(int $value): int` | Count bits needed to represent a number |
| `colorBitShift(int $value, int $inBits, int $outBits)` | Shift color bits with replication |
| `unsignedShift(int $value, int $bits)` | Right shift without sign replication |
| `mirrorBits(int $value, int $nbit)` | Mirror arbitrary bit run |
| `mirrorByte(int $n)` | Mirror 8 bits in each byte |
| `mirrorShort(int $n)` | Mirror 16 bits |
| `mirrorLong(int $n)` | Mirror 32 bits |
| `mirrorLongLong(int $n)` | Mirror 64 bits (64-bit only) |

### Endian Conversion

| Method | Description |
|--------|-------------|
| `flipEndianShort(int $n)` | Flip endian in 16-bit values |
| `flipEndianLong(int $n)` | Flip endian in 32-bit values |
| `flipEndianLongLong(int $n)` | Flip endian in 64-bit values (64-bit only) |

### Utility

| Method | Description |
|--------|-------------|
| `isSystemBigEndian(): bool` | Check if system is big endian |
| `hasLongLong(): bool` | Check if 64-bit PHP |
| `isNegativeFloat(float $value): bool` | Check if float is negative (including -0.0) |
| `isNegativeZero(float $value): bool` | Check if float is exactly -0.0 |
| `crc32(mixed $string, bool\|int $crc)` | CRC32 with file/string/stream support |

## See Also

- PHP's `array_map`, `pack`, `unpack` for related operations
