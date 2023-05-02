<?php
/**
 * TBitHelper class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

use Prado\Exceptions\TInvalidDataValueException;

/**
 * TBitHelper class.
 *
 * This class contains static functions for bit-wise and byte operations, like color
 * bit shifting, unsigned right bit shift, mirroring the order of bits, flipping
 * endian, and formatting floats into and from smaller float representations like
 * half floats (Fp16, Bf16) and mini floats (Fp8).  It also can check for negative
 * floats including negative zero.
 *
 * Shifting bits for color accuracy requires repeating the bits rather than
 * just adding extra 0/1 bits.  {@link colorBitShift} properly adds and removes bits
 * to an integer color value by replicating the bits for new bits.
 *
 * There are specific floating point conversion methods for converting float to:
 *   - Fp16 with {@link floatToFp16} and back with {@link fp16ToFloat}.
 *   - Bf16 with {@link floatToBf16} and back with {@link bf16ToFloat}.
 *   - Fp8-e5m2 with {@link floatToFp8Range} and back with {@link fp8RangeToFloat}.
 *   - Fp8-e4m3 with {@link floatToFp8Precision} and back with {@link fp8PrecisionToFloat}.
 * These functions use the general conversion functions {@link floatToFpXX} and
 * {@link fpXXToFloat} where the number of bits for the exponent and mantissa are
 * parameters. For example, 24 bit floats or 14 bit floats can be created.
 *
 * {@link mirrorBits} can mirror arbitrary runs of bits in an integer.  There is
 * quick mirroring for specific exponents of two: {@link mirrorByte} for 8 bits,
 * {@link mirrorShort} for 16 bits, {@link mirrorLong} for 32 bits, and, on 64 bit
 * instances of PHP, {@link mirrorLongLong} for 64 bits.
 *
 * There are endian byte reversal functions: {@link flipEndianShort}, {@link flipEndianLong},
 * and, on 64 bit instances of PHP, {@link flipEndianLongLong}.
 *
 * {@link bitCount} calculates the number of bits required to represent a specific
 * number. 255 return 8 bits, 256 returns 9 bits.
 *
 * {@link isNegativeFloat} is used to determine if a float has the negative bit
 * set.  It will return true on any negative float number, including negative zero.
 * {@link isNegativeZero} can check if a float is a negative zero.  PHP cannot normally
 * check for negative zero float and requires these special functions to so.
 *
 * The Levels and Masks are for O(1) time bit reversals of 8, 16, 32, and 64 bit integers.
 * The TBitHelper class automatically adjusts itself for 32 or 64 bit PHP environments.
 *
 * When quickly mirroring bits or switching endian, the high bits are also converted
 * like the low bits.  E.g. When mirroring a Byte, all bytes in the integer are
 * individually mirrored in place. When converting a Short, each short in the integer
 * will be converted in place. In the instance of a Long, for 64 bit systems will
 * convert both Longs -in place- in its LongLong (64 bit) unit integer type.
 * Converting LongLong is only supported in 64 bit PHP environments.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.3
 */
class TBitHelper
{
	public const Level1 = (PHP_INT_SIZE >= 8) ? 0x5555555555555555 : 0x55555555;
	public const NLevel1 = ~self::Level1;
	public const Mask1 = (PHP_INT_SIZE >= 8) ? 0x7FFFFFFFFFFFFFFF : 0x7FFFFFFF;
	public const Level2 = (PHP_INT_SIZE >= 8) ? 0x3333333333333333 : 0x33333333;
	public const NLevel2 = ~self::Level2;
	public const Mask2 = self::Mask1 >> 1;
	public const Level3 = (PHP_INT_SIZE >= 8) ? 0x0F0F0F0F0F0F0F0F : 0x0F0F0F0F;
	public const NLevel3 = ~self::Level3;
	public const Mask3 = self::Mask1 >> 3;
	public const Level4 = (PHP_INT_SIZE >= 8) ? 0x00FF00FF00FF00FF : 0x00FF00FF;
	public const NLevel4 = ~self::Level4;
	public const Mask4 = self::Mask1 >> 7;
	public const Level5 = (PHP_INT_SIZE >= 8) ? 0x0000FFFF0000FFFF : 0x0000FFFF;
	public const NLevel5 = ~self::Level5;
	public const Mask5 = self::Mask1 >> 15;
	public const Level6 = (PHP_INT_SIZE >= 8) ? 0x00000000FFFFFFFF : -1;
	public const NLevel6 = ~self::Level6;
	public const Mask6 = self::Mask1 >> 31;

	/**
	 * Motorola is Big Endian with the Most Significant Byte first whereas Intel uses
	 * Little Endian with the Least Significant Byte first.  This mainly only affects
	 * the binary reading and writing of data types that are 2 bytes or larger.
	 * @return bool Is the PHP environment in Big Endian Motorola Byte format.
	 */
	public static function isSystemBigEndian(): bool
	{
		static $bigEndian = null;
		if ($bigEndian === null) {
			$bigEndian = unpack('S', "\x00\x01")[1] === 1;
		}
		return $bigEndian;
	}

	/**
	 * @return bool Is the PHP environment 64 bit and supports the 64 bit LongLong type.
	 */
	public static function hasLongLong(): bool
	{
		return PHP_INT_SIZE >= 8;
	}

	/**
	 * This returns true with all negative floats, including -0.0.  Normally "$float < 0"
	 * will not include -0.0, where this function does include -0.0.
	 * @param float $value The float to check for being negative.
	 * @return bool Is a negative float.
	 */
	public static function isNegativeFloat(float $value): bool
	{
		return $value < 0 || $value === -0.0 && (ord(pack('G', $value)) & 0x80) !== 0;
	}

	/**
	 * This returns true with negative zero (-0.0).  Checking for negative zero floats
	 * requires this special function because PHP cannot be directly check for negative
	 * zero due to '-0.0 === 0.0'.
	 * @param float $value The float to check for being negative.
	 * @return bool Is a negative zero float.
	 */
	public static function isNegativeZero(float $value): bool
	{
		return $value === -0.0 && (ord(pack('G', $value)) & 0x80) !== 0;
	}

	/**
	 * Encodes a PHP float into an N-bit floating point number (in an integer) representation.
	 * This function can be configured with arbitrary number of Exponent Bits, Mantissa Bits,
	 * Exponent Bias, and IEEE Conformance (for subnormal numbers, INF, -INF, and NAN).
	 * The total number of floating point bits to be parsed is "$exponentBits + $mantissaBits + 1".
	 *
	 * With default parameter values, this functions as floatToFp16.
	 * @param float $value The PHP float to encode.
	 * @param int $exponentBits The number of bits used for the exponent, default: null for 5.
	 * @param int $mantissaBits The number of bits used for the mantissa, default: null for 10.
	 * @param null|int $exponentBias The bias to apply to the exponent. If null, it defaults to
	 *    half the maximum exponent value.  Default: null.
	 * @param bool $IEEEConformance Whether to follow the IEEE 754 standard for special values
	 *    (NaN, INF, -INF, and subnormal). Default true
	 * @throws TInvalidDataValueException on bad floating point configuration values.
	 * @return int The a short form float representation of the float $value.
	 */
	public static function floatToFpXX(float $value, ?int $exponentBits = null, ?int $mantissaBits = null, ?int $exponentBias = null, bool $IEEEConformance = true): int
	{
		$exponentBits = ($exponentBits === null) ? 5 : $exponentBits;
		$mantissaBits = ($mantissaBits === null) ? 10 : $mantissaBits;
		$exponentMaxValue = ~(-1 << $exponentBits);
		$exponentBias = ($exponentBias === null) ? $exponentMaxValue >> 1 : $exponentBias;
		if ($exponentBits <= 0 || $mantissaBits <= 0 || ($exponentBits + $mantissaBits + 1) > PHP_INT_SIZE * 8 || $exponentBias < 0 || $exponentBias > $exponentMaxValue) {
			throw new TInvalidDataValueException('bithelper_bad_fp_format', $exponentBits, $mantissaBits, $exponentBias, PHP_INT_SIZE * 8);
		}
		$sign = self::isNegativeFloat($value) ? 1 : 0;
		$value = abs($value);
		$exponent = 0;
		$mantissa = 0;

		if ($IEEEConformance && is_nan($value)) {
			$exponent = $exponentMaxValue;
			$mantissa = ~(-1 << $mantissaBits);
		} elseif ($IEEEConformance && (is_infinite($value) || $value >= pow(2, ($exponentMaxValue - 1) - $exponentBias) * (1 << $mantissaBits))) {
			$exponent = $exponentMaxValue;
		} elseif ($value == 0) {
			$mantissa = 0;
		} else {
			$exponent = floor(log($value, 2)) + $exponentBias;
			if ($exponent <= 0) {
				$mantissa = round($value / pow(2, 1 - $exponentBias - $mantissaBits));
				$exponent = 0;
			} elseif ($exponent >= $exponentMaxValue) {
				$exponent = $exponentMaxValue;
				$mantissa = 0;
			} else {
				$totalMantissaValues = (1 << $mantissaBits);
				$mantissa = round(($value / pow(2, $exponent - $exponentBias) - 1.0) * $totalMantissaValues);
				if ($mantissa === $totalMantissaValues) {
					$exponent++;
					$mantissa = 0;
				}
			}
		}
		$fpXX = ((($sign << $exponentBits) | $exponent) << $mantissaBits) | $mantissa;
		return $fpXX;
	}

	/**
	 * This encodes a PHP float into a Fp16 (1 bit sign, 5 bits exponent, 10 bits mantissa) float.
	 * @param float $value The float to encode.
	 * @param null|int $exponentBias The bias to apply to the exponent. If null, it defaults to
	 *    half the maximum exponent value.  Default: null.
	 * @return int The encoded 2 byte Fp16 float.
	 */
	public static function floatToFp16(float $value, ?int $exponentBias = null): int
	{
		return self::floatToFpXX($value, 5, 10, $exponentBias);
	}

	/**
	 * This encodes a PHP float into a Bf16 (1 bit sign, 8 bits exponent, 7 bits mantissa)
	 * float.  This preserves the range of typical 4 byte floats but drops 2 bytes of
	 * precision from 23 bits to 7 bits.
	 * @param float $value The float to encode.
	 * @param null|int $exponentBias The bias to apply to the exponent. If null, it defaults to
	 *    half the maximum exponent value.  Default: null.
	 * @return int The encoded 2 byte Bf16 float.
	 */
	public static function floatToBf16(float $value, ?int $exponentBias = null): int
	{
		return self::floatToFpXX($value, 8, 7, $exponentBias);
	}

	/**
	 * This encodes a PHP float into an FP8 (1 bit sign, 5 bits exponent, 2 bits mantissa) float.
	 * The FP8 E5M2 format is for lower precision and higher range.
	 * @param float $value The float to encode.
	 * @param null|int $exponentBias The bias to apply to the exponent. If null, it defaults to
	 *    half the maximum exponent value.  Default: null.
	 * @return int The encoded 1 byte FP8-E5M2 float.
	 */
	public static function floatToFp8Range(float $value, ?int $exponentBias = null): int
	{
		return self::floatToFpXX($value, 5, 2, $exponentBias);
	}

	/**
	 * This encodes a PHP float into an FP8 (1 bit sign, 4 bits exponent, 3 bits mantissa) float.
	 * The FP8 E4M3 format is for higher precision and lower range.
	 * @param float $value The float to encode.
	 * @param null|int $exponentBias The bias to apply to the exponent. If null, it defaults to
	 *    half the maximum exponent value.  Default: null.
	 * @return int The encoded 1 byte FP8-E4M3 float.
	 */
	public static function floatToFp8Precision(float $value, ?int $exponentBias = null): int
	{
		return self::floatToFpXX($value, 4, 3, $exponentBias);
	}

	/**
	 * Decodes an N-bit floating point encoded as an integer to a PHP floating-point number.
	 * This function can be configured with arbitrary number of Exponent Bits, Mantissa Bits,
	 * Exponent Bias, and IEEE Conformance (for subnormal numbers, INF, -INF, and NAN).
	 * The total number of floating point bits to be parsed is "$exponentBits + $mantissaBits + 1".
	 *
	 * With default parameter values, this functions as fp16ToFloat.
	 * @param int $fpXX The encoded N-bit floating point number.
	 * @param int $exponentBits The number of bits used for the exponent, default: null for 5.
	 * @param int $mantissaBits The number of bits used for the mantissa, default: null for 10.
	 * @param null|int $exponentBias The bias to apply to the exponent. If null, it defaults to
	 *    half the maximum exponent value.  Default: null.
	 * @param bool $IEEEConformance Whether to follow the IEEE 754 standard for special values
	 *    (NaN, INF, -INF, and subnormal). Default true
	 * @throws TInvalidDataValueException on bad floating point configuration values.
	 * @return float The PHP float of the encoded $fpXX float.
	 */
	public static function fpXXToFloat(int $fpXX, ?int $exponentBits = null, ?int $mantissaBits = null, ?int $exponentBias = null, bool $IEEEConformance = true): float
	{
		$exponentBits = ($exponentBits === null) ? 5 : $exponentBits;
		$mantissaBits = ($mantissaBits === null) ? 10 : $mantissaBits;
		$exponentMaxValue = ~(-1 << $exponentBits);
		if ($exponentBits <= 0 || $mantissaBits <= 0 || ($exponentBits + $mantissaBits + 1) > PHP_INT_SIZE * 8 ||
			($exponentBias !== null && ($exponentBias < 0 || $exponentBias > $exponentMaxValue))) {
			throw new TInvalidDataValueException('bithelper_bad_fp_format', $exponentBits, $mantissaBits, $exponentBias, PHP_INT_SIZE * 8);
		}
		$exponentBias = ($exponentBias === null) ? $exponentMaxValue >> 1 : $exponentBias;
		$sign = ($fpXX >> ($exponentBits + $mantissaBits)) & 0x1;
		$exponent = ($fpXX >> $mantissaBits) & $exponentMaxValue;
		$mantissa = $fpXX & ~(-1 << $mantissaBits);
		if ($IEEEConformance && $exponent == 0) { // subnormal numbers.
			$value = $mantissa * pow(2, 1 - $exponentBias - $mantissaBits);
		} elseif ($IEEEConformance && $exponent == $exponentMaxValue) {
			$value = ($mantissa == 0) ? INF : NAN;
		} else {
			$value = pow(2, $exponent - $exponentBias) * (1.0 + ($mantissa / (1 << $mantissaBits)));
		}
		if ($sign) {
			$value = -$value;
		}
		return $value;
	}

	/**
	 * This decodes a Fp16 (5 bits exponent, 10 bits mantissa) encoded float into a PHP Float.
	 * @param int $fp16 the Fp16 encoded float.
	 * @param null|int $exponentBias The bias to apply to the exponent. If null, it defaults to
	 *    half the maximum exponent value.  Default: null.
	 * @return float The Fp16 float decoded as a PHP float.
	 */
	public static function fp16ToFloat(int $fp16, ?int $exponentBias = null): float
	{
		return self::fpXXToFloat($fp16, 5, 10, $exponentBias);
	}

	/**
	 * This decodes a Bf16 (8 bits exponent, 7 bits mantissa) encoded float into a PHP
	 * Float.
	 * @param int $bf16 the BF16 encoded float.
	 * @param null|int $exponentBias The bias to apply to the exponent. If null, it defaults to
	 *    half the maximum exponent value.  Default: null.
	 * @return float The Bf16 float decoded as a PHP float.
	 */
	public static function bf16ToFloat(int $bf16, ?int $exponentBias = null): float
	{
		return self::fpXXToFloat($bf16, 8, 7, $exponentBias);
	}

	/**
	 * This decodes a FP8 (5 bits exponent, 2 bits mantissa) encoded float into a PHP Float.
	 * @param int $fp8 the FP8-E5M2 encoded float.
	 * @param null|int $exponentBias The bias to apply to the exponent. If null, it defaults to
	 *    half the maximum exponent value.  Default: null.
	 * @return float The FP8-E5M2 float decoded as a PHP float.
	 */
	public static function fp8RangeToFloat(int $fp8, ?int $exponentBias = null): float
	{
		return self::fpXXToFloat($fp8, 5, 2, $exponentBias);
	}

	/**
	 * This decodes a FP8 (4 bits exponent, 3 bits mantissa) encoded float into a PHP Float.
	 * @param int $fp8 the FP8-E4M3 encoded float.
	 * @param null|int $exponentBias The bias to apply to the exponent. If null, it defaults to
	 *    half the maximum exponent value.  Default: null.
	 * @return float The FP8-E4M3 float decoded as a PHP float.
	 */
	public static function fp8PrecisionToFloat(int $fp8, ?int $exponentBias = null): float
	{
		return self::fpXXToFloat($fp8, 4, 3, $exponentBias);
	}

	/**
	 * This calculates the number of bits required to represent a given number.
	 * eg. If there are 256 colors, then the maximum representable number in 8 bits
	 * is 255.  A $value of 255 returns 8 bits, and 256 returns 9 bits, to represent
	 * the number.
	 * @param int $value The number to calculate the bits required to represent it.
	 * @return int The number of bits required to represent $n
	 */
	public static function bitCount(int $value): int
	{
		if ($value === 0) {
			return 0;
		} elseif ($value < 0) {	// Negative numbers need one more bit.
			$value = (-$value) << 1;
		}
		if ($value < 0) {
			return PHP_INT_SIZE * 8;
		}
		return (int) ceil(log($value + 1, 2));
	}

	/**
	 * This method shifts color bits.  When removing bits, they are simply dropped.
	 * When adding bits, it replicates the existing bits for new bits to create the
	 * most accurate higher bit representation of the color.
	 * @param int $value The color value to expand or contract bits.
	 * @param int $inBits The number of bits of the input value.
	 * @param int $outBits The number of bits of the output value.
	 * @return int The $value shifted to $outBits in size.
	 * @throw TInvalidDataValueException when the $inBits or $outBits are less than
	 *   1 or greater than the Max Int Size for this PHP implementation.
	 */
	public static function colorBitShift(int $value, int $inBits, int $outBits): int
	{
		if ($inBits < 1 || $inBits > PHP_INT_SIZE * 8) {
			throw new TInvalidDataValueException("bithelper_invalid_color_in", $inBits);
		}
		if ($outBits < 1 || $outBits > PHP_INT_SIZE * 8) {
			throw new TInvalidDataValueException("bithelper_invalid_color_out", $outBits);
		}
		$dif = $outBits - $inBits;
		if ($dif > 0) {
			$return = $value;
			do {
				$dd = min($inBits, $dif);
				$return = ($return << $dd) | ($value >> ($inBits - $dd));
				$dif -= $dd;
			} while ($dif > 0);
			return $return;
		} elseif ($dif < 0) {
			$dif = -$dif;
			return ($value >> $dif) & (PHP_INT_MAX >> ($dif - 1));
		}
		return $value;
	}

	/**
	 * This does a right bit shift but the signed bit is not replicated in the high
	 * bit (with a bit-and).
	 * In normal PHP right bit shift, the signed bit is what make up any new bit in
	 * the shift.
	 * @param int $value The integer to bit shift.
	 * @param int $bits How much to shift the bits right.  Positive is right shift,
	 *   Negative is left shift.
	 * @return int The shifted integer without the high bit repeating.
	 */
	public static function unsignedShift(int $value, int $bits): int
	{
		if ($bits > 0) {
			return ($value >> $bits) & (PHP_INT_MAX >> ($bits - 1));
		} elseif ($bits < 0) {
			return $value << -$bits;
		} else {
			return $value;
		}
	}

	/**
	 * This mirrors $nbit bits from $value. For example, 0b100 becomes 0b001 @ $nbit = 3
	 * and 0x0100 become 0x0010 @ $nbit = 4.
	 * @param int $value The bits to reverse.
	 * @param int $nbit The number of bits to reverse.
	 * @throws TInvalidDataValueException when $nbits is over the maximum size of a PHP int.
	 * @return int reversed bits of $value.
	 */
	public static function mirrorBits(int $value, int $nbit): int
	{
		if ($nbit > PHP_INT_SIZE * 8) {
			throw new TInvalidDataValueException('bithelper_bad_mirror_bits', $nbit, PHP_INT_SIZE * 8);
		}
		for ($i = 0, $result = 0; $i < $nbit; $i++) {
			$result <<= 1;
			$result |= $value & 1;
			$value >>= 1;
		}
		return $result;
	}

	/**
	 * This quickly mirrors the 8 bits in each byte of $n.
	 * @param int $n The integer to mirror the bits of each byte.
	 * @return int reversed 8 bits of $n.
	 */
	public static function mirrorByte(int $n): int
	{
		$n = ((($n & self::NLevel1) >> 1) & self::Mask1) | (($n & self::Level1) << 1);
		$n = ((($n & self::NLevel2) >> 2) & self::Mask2) | (($n & self::Level2) << 2);
		return ((($n & self::NLevel3) >> 4) & self::Mask3) | (($n & self::Level3) << 4);
	}

	/**
	 * This quickly mirrors the 16 bits in each [2 byte] short of $n.
	 * @param int $n The integer to mirror the bits of each short.
	 * @return int reversed 16 bits of $n.
	 */
	public static function mirrorShort(int $n): int
	{
		$n = ((($n & self::NLevel1) >> 1) & self::Mask1) | (($n & self::Level1) << 1);
		$n = ((($n & self::NLevel2) >> 2) & self::Mask2) | (($n & self::Level2) << 2);
		$n = ((($n & self::NLevel3) >> 4) & self::Mask3) | (($n & self::Level3) << 4);
		return ((($n & self::NLevel4) >> 8) & self::Mask4) | (($n & self::Level4) << 8);

	}

	/**
	 * This quickly mirrors the 32 bits in each [4 byte] long of $n.
	 * @param int $n The integer to mirror the bits of each long.
	 * @return int reversed 32 bits of $n.
	 */
	public static function mirrorLong(int $n): int
	{
		$n = ((($n & self::NLevel1) >> 1) & self::Mask1) | (($n & self::Level1) << 1);
		$n = ((($n & self::NLevel2) >> 2) & self::Mask2) | (($n & self::Level2) << 2);
		$n = ((($n & self::NLevel3) >> 4) & self::Mask3) | (($n & self::Level3) << 4);
		$n = ((($n & self::NLevel4) >> 8) & self::Mask4) | (($n & self::Level4) << 8);
		return ((($n & self::NLevel5) >> 16) & self::Mask5) | (($n & self::Level5) << 16);
	}

	/**
	 * This quickly mirrors the 64 bits of $n.  This only works with 64 bit PHP systems.
	 * For speed, there is no check to validate that the system is 64 bit PHP.  You
	 * must do the validation if/when needed with method {@link hasLongLong}.
	 * @param int $n The 8 byte integer to mirror the bits of.
	 * @return int reversed 64 bits of $n.
	 */
	public static function mirrorLongLong(int $n): int
	{
		$n = ((($n & self::NLevel1) >> 1) & self::Mask1) | (($n & self::Level1) << 1);
		$n = ((($n & self::NLevel2) >> 2) & self::Mask2) | (($n & self::Level2) << 2);
		$n = ((($n & self::NLevel3) >> 4) & self::Mask3) | (($n & self::Level3) << 4);
		$n = ((($n & self::NLevel4) >> 8) & self::Mask4) | (($n & self::Level4) << 8);
		$n = ((($n & self::NLevel5) >> 16) & self::Mask5) | (($n & self::Level5) << 16);
		return ((($n & self::NLevel6) >> 32) & self::Mask6) | (($n & self::Level6) << 32);
	}

	/**
	 * This quickly flips the endian in each [2 byte] short of $n.
	 * @param int $n The 2 byte short to reverse the endian.
	 * @return int reversed endian of $n.
	 */
	public static function flipEndianShort(int $n): int
	{
		return ((($n & self::NLevel4) >> 8) & self::Mask4) | (($n & self::Level4) << 8);
	}

	/**
	 * This quickly flips the endian in each [4 byte] long of $n.
	 * @param int $n The 4 byte long to reverse the endian.
	 * @return int The reversed endian of $n.
	 */
	public static function flipEndianLong(int $n): int
	{
		$n = ((($n & self::NLevel4) >> 8) & self::Mask4) | (($n & self::Level4) << 8);
		return ((($n & self::NLevel5) >> 16) & self::Mask5) | (($n & self::Level5) << 16);
	}

	/**
	 * This quickly fligs the  endian of an 8 byte integer.  This only works with 64
	 * bit PHP systems. 32 bit systems will treat the bit field as floats and invariably
	 * fail.
	 *
	 * For speed, there is no check to validate that the system is 64 bit PHP.  You
	 * must do the validation if/when needed with method {@link hasLongLong}.
	 * @param int $n The 8 byte long long to reverse the endian.
	 * @return int reversed 8 bytes endian of $n.
	 */
	public static function flipEndianLongLong(int $n): int
	{
		$n = ((($n & self::NLevel4) >> 8) & self::Mask4) | (($n & self::Level4) << 8);
		$n = ((($n & self::NLevel5) >> 16) & self::Mask5) | (($n & self::Level5) << 16);
		return ((($n & self::NLevel6) >> 32) & self::Mask6) | (($n & self::Level6) << 32);
	}
}
