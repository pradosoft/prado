<?php
/**
 * TURational class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Math;

use Prado\Util\Helpers\TBitHelper;

/**
 * TURational class.
 *
 * TURational implements a fraction in the form of one unsigned integer {@link getNumerator
 *  Numerator} divided by another unsigned integer {@link getDenominator Denominator}.
 *
 * TURational is a specialization of {@link TRational} and TRational has information
 * about how these classes work.
 *
 * INF is "4294967295/0" and NAN (Not A Number) has the denominator equal zero
 * (to avoid a divide by zero error).
 *
 * When setting a {@link setNumerator Numerator} and {@link setDenominator Denominator},
 * the PHP instance is checked if it is 32 bit or 64 bit.  64 Bit PHP can represent
 * integers in the range [2147483648, 4294967295] as an integer, but on a 32 bit
 * PHP instance, these high bit integers are converted to float to be more accurately
 * represented.
 *
 * The Rational data format is used by EXIF and, in particular, the GPS Image File
 * Directory.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @see TRational
 * @since 4.2.3
 */
class TURational extends TRational
{
	/**
	 * @return bool Is the class unsigned.  Returns true.
	 */
	public static function getIsUnsigned(): bool
	{
		return true;
	}

	/**
	 * This only accepts 0 and positive values. For 32 bit systems this accepts a float
	 * to represent numbers larger than PHP_INT_MAX.
	 * @param float|int|string $value The numerator.
	 */
	public function setNumerator($value): TURational
	{
		$value = min(max(0, $value), TBitHelper::PHP_INT32_UMAX);
		if (PHP_INT_SIZE > 4 || $value <= PHP_INT_MAX) {
			$this->_numerator = (int) $value;
		} else {
			$this->_numerator = (float) $value;
		}
		return $this;
	}

	/**
	 * This only accepts 0 and positive values. For 32 bit systems this accepts a float
	 * to represent numbers larger than PHP_INT_MAX.
	 * @param float|int|string $value The denominator.
	 */
	public function setDenominator($value): TURational
	{
		$value = min(max(0, $value), TBitHelper::PHP_INT32_UMAX);
		if (PHP_INT_SIZE > 4 || $value <= PHP_INT_MAX) {
			$this->_denominator = (int) $value;
		} else {
			$this->_denominator = (float) $value;
		}
		return $this;
	}

	/**
	 * This returns the float value of the Numerator divided by the denominator.
	 * Returns INF (Infinity) float value if the {@link getNumerator Numerator} is
	 * 0xFFFFFFFF (4294967295) and {@link getDenominator Denominator} is 0.   Returns
	 * NAN (Not A Number) float value if the {@link getDenominator Denominator} is zero.
	 * @return float The float value of the Numerator divided by denominator.
	 */
	public function getValue(): float
	{
		if ($this->_numerator === TBitHelper::PHP_INT32_UMAX && $this->_denominator === 0) {
			return INF;
		}
		if ($this->_denominator === 0) {
			return NAN;
		}
		return ((float) $this->_numerator) / ((float) $this->_denominator);
	}
}
