<?php
/**
 * TRational class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Math;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Util\TBitHelper;

/**
 * TRational class.
 *
 * TRational implements a fraction in the form of one integer {@link getNumerator
 * Numerator} divided by another integer {@link getDenominator Denominator}.
 *
 * The class can be {@link __construct initialized} or its {@link setValue Value}
 * set as a string, a float value, or an array.  A string in the format
 * `$numerator . '/' . $denominator`, eg. "21/13", will set the both the numerator
 * and denominator.  A string that is simply a numeric will be interpreted as a
 * float value.  An array in the format of `[$numerator, $denominator]` can be used
 * to set the numerator and denominator as well.
 *
 * Setting Float values are processed through a Continued Fraction function to a
 * specified tolerance to calculate the integer numerator and integer denominator.
 * INF is "-1/0" and NAN (Not A Number) has the denominator equal zero (to avoid a
 * divide by zero error).
 *
 * TRational is {@link __invoke invokable} to get and set the value. By invoking
 * a TRational with a parameter, the value is set.  By invoking a TRational without
 * a parameter the value is retrieved.
 *
 * The numerater and denominator can be accessed by {@link getNumerator} and {@link
 * getDenominator}, respectively.  These values can be accessed by array as well,
 * where the numerator is mapped to `[0]` and `['numerator']` and the denominator is
 * mapped to `[1]` and `['denominator']`.
 *
 * TRational implements {@link __toString} and outputs a string of `$numerator . '/'
 * . $denominator`, the string format for rationals.  eg.  "13/8".
 *
 * The Rational data format is used by EXIF and, in particular, the GPS Image File
 * Directory of EXIF.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.3
 * @see https://en.wikipedia.org/wiki/Continued_fraction
 */
class TRational implements \ArrayAccess
{
	public const NUMERATOR = 'numerator';

	public const DENOMINATOR = 'denominator';

	/* The Default Tolerance when null is provided for a tolerance */
	public const DEFAULT_TOLERANCE = 1.0e-6;

	/** @var float|int The numerator of the rational number. default 0. */
	protected $_numerator = 0;

	/** @var float|int The denominator of the rational number. default 1. */
	protected $_denominator = 1;

	/**
	 * @return bool Is the class unsigned. Returns false.
	 */
	public static function getIsUnsigned(): bool
	{
		return false;
	}

	/**
	 * This initializes a TRational with no value [null], a float that gets deconstructed
	 * into a numerator and denominator, a string with the numerator and denominator
	 * with a '/' between them, an array with [0 => $numerator, 1 => $denominator],
	 * or both the numerator and denominator as two parameters.
	 * @param null|array|false|float|int|string $numerator Null or false as nothing,
	 *   int and float as values, array of numerator and denominator.
	 * @param null|false|numeric $denominator The denominator. Default null for the
	 *   $numerator is a value to be deconstructed.
	 */
	public function __construct($numerator = null, $denominator = null)
	{
		if ($numerator !== null && $numerator !== false) {
			if ($denominator === null || $denominator === false) {
				$this->setValue($numerator);
			} else {
				$this->setValue([$numerator, $denominator]);
			}
		}
	}

	/**
	 * @return float|int The numerator.
	 */
	public function getNumerator()
	{
		return $this->_numerator;
	}

	/**
	 * @param float|int|string $value The numerator.
	 * @return TRational Returns $this.
	 */
	public function setNumerator($value): TRational
	{
		$this->_numerator = (int) min(max(TBitHelper::PHP_INT32_MIN, $value), TBitHelper::PHP_INT32_MAX);
		return $this;
	}

	/**
	 * Unless specifically set, the denominator usually only has a positive value.
	 * @return float|int The denominator.
	 */
	public function getDenominator()
	{
		return $this->_denominator;
	}

	/**
	 * @param float|int|string $value The denominator.
	 * @return TRational Returns $this.
	 */
	public function setDenominator($value): TRational
	{
		$this->_denominator = (int) min(max(TBitHelper::PHP_INT32_MIN, $value), TBitHelper::PHP_INT32_MAX);
		return $this;
	}

	/**
	 * This returns the float value of the Numerator divided by the denominator.
	 * Returns INF (Infinity) float value if the {@link getNumerator Numerator} is
	 * 0xFFFFFFFF (-1) and {@link getDenominator Denominator} is 0.   Returns NAN
	 * (Not A Number) float value if the {@link getDenominator Denominator} is zero.
	 * @return float The float value of the Numerator divided by denominator.
	 */
	public function getValue(): float
	{
		if ($this->_numerator === -1 && $this->_denominator === 0) {
			return INF;
		}
		if ($this->_denominator === 0) {
			return NAN;
		}
		return ((float) $this->_numerator) / ((float) $this->_denominator);
	}

	/**
	 * When setting a float value, this computes the numerator and denominator from
	 * the Continued Fraction mathematical computation to a specific $tolerance.
	 * @param array|numeric|string $value The numeric to compute the int numerator and
	 *   denominator, or a string in the format numerator - '/' character - and then
	 *   the denominator; eg. '511/333'. or an array of [numerator, denominator].
	 * @param ?float $tolerance the tolerance to compute the numerator and denominator
	 *   from the numeric $value.  Default null for "1.0e-6".
	 * @return TRational Returns $this.
	 */
	public function setValue($value, ?float $tolerance = null): TRational
	{
		$numerator = $denominator = null;
		if (is_array($value)) {
			if (array_key_exists(0, $value)) {
				$numerator = $value[0];
			} elseif (array_key_exists(self::NUMERATOR, $value)) {
				$numerator = $value[self::NUMERATOR];
			} else {
				$numerator = 0;
			}
			if (array_key_exists(1, $value)) {
				$denominator = $value[1];
			} elseif (array_key_exists(self::DENOMINATOR, $value)) {
				$denominator = $value[self::DENOMINATOR];
			} else {
				$denominator = null;
			}
			if ($denominator === null) {
				$value = $numerator;
				$numerator = null;
			}
		} elseif (is_string($value) && strpos($value, '/') !== false) {
			[$numerator, $denominator] = explode('/', $value, 2);
		}
		$unsigned = $this->getIsUnsigned();
		if ($numerator !== null) {
			$numerator = (float) $numerator;
			$denominator = (float) $denominator;
			if ($unsigned) {
				$negNum = $numerator < 0;
				$negDen = $denominator < 0;
				if ($negNum && $negDen) {
					$numerator = -$numerator;
					$denominator = -$denominator;
				} elseif ($negNum ^ $negDen) {
					$numerator = 0;
					$denominator = 1;
				}
			}
			$max = $unsigned ? TBitHelper::PHP_INT32_UMAX : TBitHelper::PHP_INT32_MAX;
			if ($numerator > $max || $denominator > $max || (!$unsigned && ($numerator < TBitHelper::PHP_INT32_MIN || $denominator < TBitHelper::PHP_INT32_MIN))) {
				$value = ($denominator === 0) ? NAN : $numerator / $denominator;
			} else {
				$this->setNumerator($numerator);
				$this->setDenominator($denominator);
				return $this;
			}
		}
		if ($value !== null) {
			[$this->_numerator, $this->_denominator] = self::float2rational((float) $value, $tolerance, $unsigned);
		}
		return $this;
	}

	/**
	 * This gets and sets the value of the Rational.  When a parameter is supplied, the
	 * value is set.  Without the parameter, this method returns the value.
	 * @param null|mixed $value The Value to set the Rational.  Default null for getting
	 *   the rational value.
	 * @param ?float $tolerance The tolerance to compute the numerator and denominator
	 *   from the $value. Default null for "1.e-6".
	 * @return float|TRational Returns the Value without a parameter and returns $this
	 *   when setting with a parameter.
	 */
	public function __invoke($value = null, ?float $tolerance = null)
	{
		if ($value !== null) {
			$this->setValue($value, $tolerance);
			return $this;
		} else {
			return $this->getValue();
		}
	}

	/**
	 * @return string Returns a string of the Numerator - '/' character - and then the
	 *   denominator.  eg. "13/8"
	 */
	public function __toString(): string
	{
		$n = $this->_numerator;
		if (is_float($n)) {
			$n = number_format($n, 0, '.', '');
		}
		$d = $this->_denominator;
		if (is_float($d)) {
			$d = number_format($d, 0, '.', '');
		}
		return $n . '/' . $d;
	}

	/**
	 * @return array returns an array of [$numerator, $denominator]
	 */
	public function toArray()
	{
		return [$this->_numerator, $this->_denominator];
	}

	/**
	 * Checks for the existence of the values TRational uses: 0, 1, 'numerator', and
	 * 'denominator'.
	 * @param mixed $offset The numerator or denominator of the TRational.
	 * @return bool Does the property exist for the TRational.
	 */
	public function offsetExists(mixed $offset): bool
	{
		if (is_numeric($offset) && ($offset == 0 || $offset == 1) || is_string($offset) && ($offset === self::NUMERATOR || $offset === self::DENOMINATOR)) {
			return true;
		}
		return false;
	}

	/**
	 * This is a convenience method for getting the numerator and denominator.
	 * Index '0' and 'numerator' will get the {@link getNumerator Numerator}, and
	 * Index '1' and 'denominator' will get the {@link getDenominator Denominator}.
	 * @param mixed $offset Which property of the Rational to retrieve.
	 * @throws TInvalidDataValueException When $offset is not a property of the Rational.
	 * @return mixed The numerator or denominator.
	 */
	public function offsetGet(mixed $offset): mixed
	{
		if (is_numeric($offset)) {
			if ($offset == 0) {
				return $this->getNumerator();
			} elseif ($offset == 1) {
				return $this->getDenominator();
			}
		} elseif (is_string($offset)) {
			if ($offset == self::NUMERATOR) {
				return $this->getNumerator();
			} elseif ($offset == self::DENOMINATOR) {
				return $this->getDenominator();
			}
		}
		throw new TInvalidDataValueException('rational_bad_offset', $offset);
	}

	/**
	 * This is a convenience method for setting the numerator and denominator.
	 * Index '0' and 'numerator' will set the {@link setNumerator Numerator}, and
	 * Index '1' and 'denominator' will set the {@link setDenominator Denominator}.
	 * @param mixed $offset Which property to set.
	 * @param mixed $value The numerator or denominator.
	 * @throws TInvalidDataValueException When $offset is not a property of the Rational.
	 */
	public function offsetSet(mixed $offset, mixed $value): void
	{
		if (is_numeric($offset)) {
			if ($offset == 0) {
				$this->setNumerator($value);
				return;
			} elseif ($offset == 1) {
				$this->setDenominator($value);
				return;
			}
		} elseif (is_string($offset)) {
			if ($offset == self::NUMERATOR) {
				$this->setNumerator($value);
				return;
			} elseif ($offset == self::DENOMINATOR) {
				$this->setDenominator($value);
				return;
			}
		}
		throw new TInvalidDataValueException('rational_bad_offset', $offset);
	}

	/**
	 * This is a convenience method for resetting the numerator and denominator to
	 * default.  Index '0' and 'numerator' will reset the {@link setNumerator Numerator}
	 * to "0", and Index '1' and 'denominator' will reset the {@link setDenominator
	 * Denominator} to "1".
	 * @param mixed $offset Which property to reset.
	 * @throws TInvalidDataValueException When $offset is not a property of the Rational.
	 */
	public function offsetUnset(mixed $offset): void
	{
		if (is_numeric($offset)) {
			if ($offset == 0) {
				$this->setNumerator(0);
				return;
			} elseif ($offset == 1) {
				$this->setDenominator(1);
				return;
			}
		} elseif (is_string($offset)) {
			if ($offset == self::NUMERATOR) {
				$this->setNumerator(0);
				return;
			} elseif ($offset == self::DENOMINATOR) {
				$this->setDenominator(1);
				return;
			}
		}
		throw new TInvalidDataValueException('rational_bad_offset', $offset);
	}

	/**
	 * This uses the Continued Fraction to make a float into a fraction of two integers.
	 * 	- Given INF, this returns [0xFFFFFFFF, 0].
	 *  - Given NAN, this returns [0, 0].
	 *  - Given 0 or values proximal to 0, this returns [0, 1].
	 * Only the numerator can go negative if the $value is negative.
	 * @param float $value The float value to deconstruct into a fraction of two integers.
	 * @param float $tolerance How precise does the continued fraction need to be to break.  Default 1.e-6
	 * @param ?bool $unsigned Is the result an unsigned 32 bit int (vs signed 32 bit int), default false
	 * @return array An array of numerator at [0] and denominator at [1].
	 * @see https://en.wikipedia.org/wiki/Continued_fraction
	 */
	public static function float2rational(float $value, ?float $tolerance = null, ?bool $unsigned = false): array
	{
		if (is_infinite($value)) {
			return [$unsigned ? TBitHelper::PHP_INT32_UMAX : -1, 0];
		}
		if (is_nan($value)) {
			return [0, 0];
		}
		if ($value === 0.0 || ($unsigned && $value < 0.5 / TBitHelper::PHP_INT32_UMAX) || (!$unsigned && abs($value) < 0.5 / TBitHelper::PHP_INT32_MAX)) {
			return [0, 1];
		}
		if ($unsigned) {
			if ($value > TBitHelper::PHP_INT32_UMAX) {
				return [TBitHelper::PHP_INT32_UMAX, 1];
			} elseif ($value < 1.5 / TBitHelper::PHP_INT32_UMAX) {
				return [1, TBitHelper::PHP_INT32_UMAX];
			}
		} else {
			if ($value > TBitHelper::PHP_INT32_MAX) {
				return [TBitHelper::PHP_INT32_MAX, 1];
			} elseif ($value < TBitHelper::PHP_INT32_MIN) {
				return [TBitHelper::PHP_INT32_MIN, 1];
			} elseif (abs($value) < 1.5 / TBitHelper::PHP_INT32_MAX) {
				return [1, TBitHelper::PHP_INT32_MAX];
			}
		}
		if ($tolerance === null) {
			$tolerance = self::DEFAULT_TOLERANCE;
		}
		$sign = $value < 0 ? -1 : 1;
		$offset = $value < 0 ? 1.0 : 0.0; // Negative values go to +1 max over positive max.
		$value = abs($value);
		$h = 1.0;
		$lh = 0.0;
		$k = 0.0;
		$lk = 1.0;
		$b = 1.0 / $value;
		$tolerance *= $value;
		do {
			$b = 1.0 / $b;
			$a = floor($b);
			$tmp = $h;
			$h = $a * $h + $lh;
			$lh = $tmp;
			$tmp = $k;
			$k = $a * $k + $lk;
			$lk = $tmp;
			if ($h > ($unsigned ? TBitHelper::PHP_INT32_UMAX - 1 : (TBitHelper::PHP_INT32_MAX + $offset)) || $k > ($unsigned ? TBitHelper::PHP_INT32_UMAX : TBitHelper::PHP_INT32_MAX)) {
				$h = $lh;
				$k = $lk;
				break;
			}
			$b = $b - $a;
		} while ($b !== 0.0 && abs($value - $h / $k) > $tolerance);
		if (PHP_INT_SIZE > 4 || $h <= PHP_INT_MAX + $offset && $k <= PHP_INT_MAX) {
			return [$sign * ((int) $h), ((int) $k)];
		} elseif ($h <= PHP_INT_MAX + $offset) {
			return [$sign * ((int) $h), $k];
		} elseif ($k <= PHP_INT_MAX) {
			return [$sign * $h, ((int) $k)];
		} else {
			return [$sign * $h, $k];
		}
	}
}
