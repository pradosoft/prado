<?php
/**
 * TBitHelper class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Helpers;

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
 * just adding extra 0/1 bits.  {@see colorBitShift} properly adds and removes bits
 * to an integer color value by replicating the bits for new bits.
 *
 * There are specific floating point conversion methods for converting float to:
 *   - Fp16 with {@see floatToFp16} and back with {@see fp16ToFloat}.
 *   - Bf16 with {@see floatToBf16} and back with {@see bf16ToFloat}.
 *   - Fp8-e5m2 with {@see floatToFp8Range} and back with {@see fp8RangeToFloat}.
 *   - Fp8-e4m3 with {@see floatToFp8Precision} and back with {@see fp8PrecisionToFloat}.
 * These functions use the general conversion functions {@see floatToFpXX} and
 * {@see fpXXToFloat} where the number of bits for the exponent and mantissa are
 * parameters. For example, 24 bit floats or 14 bit floats can be created.
 *
 * {@see mirrorBits} can mirror arbitrary runs of bits in an integer.  There is
 * quick mirroring for specific exponents of two: {@see mirrorByte} for 8 bits,
 * {@see mirrorShort} for 16 bits, {@see mirrorLong} for 32 bits, and, on 64 bit
 * instances of PHP, {@see mirrorLongLong} for 64 bits.
 *
 * There are endian byte reversal functions: {@see flipEndianShort}, {@see flipEndianLong},
 * and, on 64 bit instances of PHP, {@see flipEndianLongLong}.
 *
 * {@see bitCount} calculates the number of bits required to represent a specific
 * number. 255 return 8 bits, 256 returns 9 bits.
 *
 * {@see isNegativeFloat} is used to determine if a float has the negative bit
 * set.  It will return true on any negative float number, including negative zero.
 * {@see isNegativeZero} can check if a float is a negative zero.  PHP cannot normally
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
 * @since 4.3.0
 */
class TBitHelper
{
	// Defined constants for 32 bit computation
	public const PHP_INT32_MIN = -2147483648;	// 0x80000000
	public const PHP_INT32_MAX = 2147483647;	// 0x7FFFFFFF
	// on 32 bit systems the PHP_INT64_UMAX is a float and not a integer.
	public const PHP_INT32_UMAX = 4294967295;	// 0xFFFFFFFF (unsigned)
	public const PHP_INT32_MASK = (PHP_INT_SIZE > 4) ? self::PHP_INT32_UMAX : -1;

	// Defined constants for 64 bit computation
	//   on 32 bit systems these values are only approximate floats and not integers.
	public const PHP_INT64_MIN = -9223372036854775808;	// 0x80000000_00000000
	public const PHP_INT64_MAX = 9223372036854775807;	// 0x7FFFFFFF_FFFFFFFF
	//PHP_INT64_UMAX is a float that only approximates the maximum, unless using 16 byte int
	public const PHP_INT64_UMAX = 18446744073709551615;	// 0xFFFFFFFF_FFFFFFFF (unsigned)
	public const PHP_INT64_MASK = -1; // Assuming 64 bit is validated.

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
	 * This is a CRC32 replacement multi-tool.  This acts exactly as crc32 with the
	 * added functionality that it accepts file paths (when $crc = true) which computes
	 * the CRC32 of the file.  This also accepts a $crc computed from existing data and
	 * continues to update the $crc with new data form $string as if $string were appended.
	 *
	 * If an array is passed in $string, [0] is the string data, filepath, or stream
	 * resource, element [1] is the size to read, and element [2] is the startOffset.
	 * If an array is passed, $crc = true still means that the $string is a FilePath.
	 * If the $string is a stream-resource, it reads until fgetc returns false or '',
	 * or size is hit.
	 *
	 * If using this on a file (with $crc = true) then $crc2 can be used for the existing
	 * crc32 for continued computation.
	 *
	 * A continued CRC32 can also be generated with HashContext using {@link hash_init},
	 * {@link hash_update}, and {@link hash_update_stream}, and {@link hash_final}.
	 * @param mixed $string String of Data, File Path, Stream Resource, or array.
	 *   An Array format is [0] => String Data, File Path, or Stream Resource, [1] is
	 *   the total size to read, and [2] is the startOffset.
	 * @param bool|int $crc The running CRC32 to continue calculating.  When true,
	 *   this expects $string to be a File Path rather than data.  Default 0 for normal
	 *   crc32 function without any prior running data.
	 * @param ?int $crc2 The existing CRC to update when specifying $string as a file
	 *   (with $crc = true).  Default null for new initial $crc for a file.
	 */
	public static function crc32(mixed $string, bool|int $crc = 0, ?int $crc2 = null): false|int
	{
		static $crc_table = [
			0x00000000, 0x77073096, 0xEE0E612C, 0x990951BA, 0x076DC419, 0x706AF48F, 0xE963A535, 0x9E6495A3,
			0x0EDB8832, 0x79DCB8A4, 0xE0D5E91E, 0x97D2D988, 0x09B64C2B, 0x7EB17CBD, 0xE7B82D07, 0x90BF1D91,
			0x1DB71064, 0x6AB020F2, 0xF3B97148, 0x84BE41DE, 0x1ADAD47D, 0x6DDDE4EB, 0xF4D4B551, 0x83D385C7,
			0x136C9856, 0x646BA8C0, 0xFD62F97A, 0x8A65C9EC, 0x14015C4F, 0x63066CD9, 0xFA0F3D63, 0x8D080DF5,
			0x3B6E20C8, 0x4C69105E, 0xD56041E4, 0xA2677172, 0x3C03E4D1, 0x4B04D447, 0xD20D85FD, 0xA50AB56B,
			0x35B5A8FA, 0x42B2986C, 0xDBBBC9D6, 0xACBCF940, 0x32D86CE3, 0x45DF5C75, 0xDCD60DCF, 0xABD13D59,
			0x26D930AC, 0x51DE003A, 0xC8D75180, 0xBFD06116, 0x21B4F4B5, 0x56B3C423, 0xCFBA9599, 0xB8BDA50F,
			0x2802B89E, 0x5F058808, 0xC60CD9B2, 0xB10BE924, 0x2F6F7C87, 0x58684C11, 0xC1611DAB, 0xB6662D3D,
			0x76DC4190, 0x01DB7106, 0x98D220BC, 0xEFD5102A, 0x71B18589, 0x06B6B51F, 0x9FBFE4A5, 0xE8B8D433,
			0x7807C9A2, 0x0F00F934, 0x9609A88E, 0xE10E9818, 0x7F6A0DBB, 0x086D3D2D, 0x91646C97, 0xE6635C01,
			0x6B6B51F4, 0x1C6C6162, 0x856530D8, 0xF262004E, 0x6C0695ED, 0x1B01A57B, 0x8208F4C1, 0xF50FC457,
			0x65B0D9C6, 0x12B7E950, 0x8BBEB8EA, 0xFCB9887C, 0x62DD1DDF, 0x15DA2D49, 0x8CD37CF3, 0xFBD44C65,
			0x4DB26158, 0x3AB551CE, 0xA3BC0074, 0xD4BB30E2, 0x4ADFA541, 0x3DD895D7, 0xA4D1C46D, 0xD3D6F4FB,
			0x4369E96A, 0x346ED9FC, 0xAD678846, 0xDA60B8D0, 0x44042D73, 0x33031DE5, 0xAA0A4C5F, 0xDD0D7CC9,
			0x5005713C, 0x270241AA, 0xBE0B1010, 0xC90C2086, 0x5768B525, 0x206F85B3, 0xB966D409, 0xCE61E49F,
			0x5EDEF90E, 0x29D9C998, 0xB0D09822, 0xC7D7A8B4, 0x59B33D17, 0x2EB40D81, 0xB7BD5C3B, 0xC0BA6CAD,
			0xEDB88320, 0x9ABFB3B6, 0x03B6E20C, 0x74B1D29A, 0xEAD54739, 0x9DD277AF, 0x04DB2615, 0x73DC1683,
			0xE3630B12, 0x94643B84, 0x0D6D6A3E, 0x7A6A5AA8, 0xE40ECF0B, 0x9309FF9D, 0x0A00AE27, 0x7D079EB1,
			0xF00F9344, 0x8708A3D2, 0x1E01F268, 0x6906C2FE, 0xF762575D, 0x806567CB, 0x196C3671, 0x6E6B06E7,
			0xFED41B76, 0x89D32BE0, 0x10DA7A5A, 0x67DD4ACC, 0xF9B9DF6F, 0x8EBEEFF9, 0x17B7BE43, 0x60B08ED5,
			0xD6D6A3E8, 0xA1D1937E, 0x38D8C2C4, 0x4FDFF252, 0xD1BB67F1, 0xA6BC5767, 0x3FB506DD, 0x48B2364B,
			0xD80D2BDA, 0xAF0A1B4C, 0x36034AF6, 0x41047A60, 0xDF60EFC3, 0xA867DF55, 0x316E8EEF, 0x4669BE79,
			0xCB61B38C, 0xBC66831A, 0x256FD2A0, 0x5268E236, 0xCC0C7795, 0xBB0B4703, 0x220216B9, 0x5505262F,
			0xC5BA3BBE, 0xB2BD0B28, 0x2BB45A92, 0x5CB36A04, 0xC2D7FFA7, 0xB5D0CF31, 0x2CD99E8B, 0x5BDEAE1D,
			0x9B64C2B0, 0xEC63F226, 0x756AA39C, 0x026D930A, 0x9C0906A9, 0xEB0E363F, 0x72076785, 0x05005713,
			0x95BF4A82, 0xE2B87A14, 0x7BB12BAE, 0x0CB61B38, 0x92D28E9B, 0xE5D5BE0D, 0x7CDCEFB7, 0x0BDBDF21,
			0x86D3D2D4, 0xF1D4E242, 0x68DDB3F8, 0x1FDA836E, 0x81BE16CD, 0xF6B9265B, 0x6FB077E1, 0x18B74777,
			0x88085AE6, 0xFF0F6A70, 0x66063BCA, 0x11010B5C, 0x8F659EFF, 0xF862AE69, 0x616BFFD3, 0x166CCF45,
			0xA00AE278, 0xD70DD2EE, 0x4E048354, 0x3903B3C2, 0xA7672661, 0xD06016F7, 0x4969474D, 0x3E6E77DB,
			0xAED16A4A, 0xD9D65ADC, 0x40DF0B66, 0x37D83BF0, 0xA9BCAE53, 0xDEBB9EC5, 0x47B2CF7F, 0x30B5FFE9,
			0xBDBDF21C, 0xCABAC28A, 0x53B39330, 0x24B4A3A6, 0xBAD03605, 0xCDD70693, 0x54DE5729, 0x23D967BF,
			0xB3667A2E, 0xC4614AB8, 0x5D681B02, 0x2A6F2B94, 0xB40BBE37, 0xC30C8EA1, 0x5A05DF1B, 0x2D02EF8D,
		];
		$length = null;
		$startOffset = 0;
		$close = false;
		if (is_array($string)) {
			$startOffset = $string[2] ?? $string['offset'] ?? 0;
			$length = $string[1] ?? $string['length'] ?? null;
			$string = $string[0] ?? $string['source'] ?? null;
		}
		if ($crc === false) {
			$crc = $crc2 === null ? 0 : $crc2;
		}
		if (is_string($string)) {
			if (is_int($crc)) {
				if ($length !== null || $startOffset) {
					$string = substr($string, $startOffset, $length);
				}
				if ($crc === 0) {
					return crc32($string);
				}
				$crc ^= 0xFFFFFFFF;
				$length = strlen($string);
				for ($i = 0; $i < $length; $i++) {
					$crc = (($crc >> 8) & 0x00FFFFFF) ^ $crc_table[($crc & 0xFF) ^ ord($string[$i])];
				}
				$crc ^= 0xFFFFFFFF;
				return $crc;
			} elseif (realpath($string) || preg_match('/^[-+\.\w\d]{1,20}\:\/\//i', $string)) {
				if ($length === null && !$startOffset && !$crc2) {
					$hash = hash_file('crc32b', $string, true);
					$value = unpack('N', $hash)[1];
					if(PHP_INT_SIZE === 4 && $value > self::PHP_INT32_MAX) {
						$value = (int) ($value - self::PHP_INT32_UMAX - 1);
					}
					return $value;
				}
				$string = fopen($string, 'rb');
				if (!$string) {
					return false;
				}
				$close = true;
			}
		}
		if (is_resource($string) && get_resource_type($string) === 'stream') {
			if ($crc === true) {
				$crc = $crc2 === null ? 0 : $crc2;
			}
			if ($startOffset) {
				$meta = stream_get_meta_data($string);
				if ($meta['seekable']) {
					fseek($string, $startOffset);
				} else {
					fread($string, $startOffset);
				}
			}
			$crc ^= 0xFFFFFFFF;
			while($length === null || $length > 0) {
				$d = fgetc($string);
				if ($d === false || strlen($d) === 0) {
					break;
				}
				$crc = (($crc >> 8) & 0x00FFFFFF) ^ $crc_table[($crc & 0xFF) ^ ord($d)];
				if ($length !== null) {
					$length--;
				}
			}
			$crc ^= 0xFFFFFFFF;
			if ($close) {
				fclose($string);
			}
			return $length === null || $length === 0 ? $crc : false;
		}
		return false;
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
			$mantissa = 1 << ($mantissaBits - 1);
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
	 * must do the validation if/when needed with method {@see hasLongLong}.
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
	 * must do the validation if/when needed with method {@see hasLongLong}.
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
