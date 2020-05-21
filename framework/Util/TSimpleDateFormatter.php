<?php
/**
 * TSimpleDateFormatter class file
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Util
 */

namespace Prado\Util;

use Prado\Prado;
use Prado\Exceptions\TInvalidDataValueException;

/**
 * TSimpleDateFormatter class.
 *
 * Formats and parses dates using the SimpleDateFormat pattern.
 * This pattern is compatible with the I18N and java's SimpleDateFormatter.
 * <code>
 * Pattern |      Description
 * ----------------------------------------------------
 * d       | Day of month 1 to 31, no padding
 * dd      | Day of monath 01 to 31, zero leading
 * M       | Month digit 1 to 12, no padding
 * MM      | Month digit 01 to 12, zero leading
 * yy      | 2 year digit, e.g., 96, 05
 * yyyy    | 4 year digit, e.g., 2005
 * ----------------------------------------------------
 * </code>
 *
 * Usage example, to format a date
 * <code>
 * $formatter = new TSimpleDateFormatter("dd/MM/yyyy");
 * echo $formatter->format(time());
 * </code>
 *
 * To parse the date string into a date timestamp.
 * <code>
 * $formatter = new TSimpleDateFormatter("d-M-yyyy");
 * echo $formatter->parse("24-6-2005");
 * </code>
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Util
 * @since 3.0
 */
class TSimpleDateFormatter
{
	/**
	 * Formatting pattern.
	 * @var string
	 */
	private $pattern;

	/**
	 * Charset, default is 'UTF-8'
	 * @var string
	 */
	private $charset = 'UTF-8';

	/**
	 * Constructor, create a new date time formatter.
	 * @param string $pattern formatting pattern.
	 * @param string $charset pattern and value charset
	 */
	public function __construct($pattern, $charset = 'UTF-8')
	{
		$this->setPattern($pattern);
		$this->setCharset($charset);
	}

	/**
	 * @return string formatting pattern.
	 */
	public function getPattern()
	{
		return $this->pattern;
	}

	/**
	 * @param string $pattern formatting pattern.
	 */
	public function setPattern($pattern)
	{
		$this->pattern = $pattern;
	}

	/**
	 * @return string formatting charset.
	 */
	public function getCharset()
	{
		return $this->charset;
	}

	/**
	 * @param string $charset formatting charset.
	 */
	public function setCharset($charset)
	{
		$this->charset = $charset;
	}

	/**
	 * Format the date according to the pattern.
	 * @param int|string $value the date to format, either integer or a string readable by strtotime.
	 * @return string formatted date.
	 */
	public function format($value)
	{
		$dt = $this->getDate($value);
		$bits['yyyy'] = $dt->format('Y');
		$bits['yy'] = $dt->format('y');

		$bits['MM'] = $dt->format('m');
		$bits['M'] = $dt->format('n');

		$bits['dd'] = $dt->format('d');
		$bits['d'] = $dt->format('j');

		$pattern = preg_replace('/M{3,4}/', 'MM', $this->pattern);
		return str_replace(array_keys($bits), $bits, $pattern);
	}

	public function getMonthPattern()
	{
		if (is_int(strpos($this->pattern, 'MMMM'))) {
			return 'MMMM';
		}
		if (is_int(strpos($this->pattern, 'MMM'))) {
			return 'MMM';
		}
		if (is_int(strpos($this->pattern, 'MM'))) {
			return 'MM';
		}
		if (is_int(strpos($this->pattern, 'M'))) {
			return 'M';
		}
		return false;
	}

	public function getDayPattern()
	{
		if (is_int(strpos($this->pattern, 'dd'))) {
			return 'dd';
		}
		if (is_int(strpos($this->pattern, 'd'))) {
			return 'd';
		}
		return false;
	}

	public function getYearPattern()
	{
		if (is_int(strpos($this->pattern, 'yyyy'))) {
			return 'yyyy';
		}
		if (is_int(strpos($this->pattern, 'yy'))) {
			return 'yy';
		}
		return false;
	}

	public function getDayMonthYearOrdering()
	{
		$ordering = [];
		if (is_int($day = strpos($this->pattern, 'd'))) {
			$ordering['day'] = $day;
		}
		if (is_int($month = strpos($this->pattern, 'M'))) {
			$ordering['month'] = $month;
		}
		if (is_int($year = strpos($this->pattern, 'yy'))) {
			$ordering['year'] = $year;
		}
		asort($ordering);
		return array_keys($ordering);
	}

	/**
	 * Gets the time stamp from string or integer.
	 * @param int|string $value date to parse
	 * @return array date info array
	 */
	private function getDate($value)
	{
		if (is_numeric($value)) {
			$date = new \DateTime;
			$date->setTimeStamp($value);
		} else {
			$date = new \DateTime($value);
		}
		return $date;
	}

	/**
	 * @param mixed $value
	 * @return bool true if the given value matches with the date pattern.
	 */
	public function isValidDate($value)
	{
		if ($value === null) {
			return false;
		} else {
			return $this->parse($value, false) !== null;
		}
	}

	/**
	 * Parse the string according to the pattern.
	 * @param int|string $value date string or integer to parse
	 * @param bool $defaultToCurrentTime
	 * @throws TInvalidDataValueException if date string is malformed.
	 * @return int date time stamp
	 */
	public function parse($value, $defaultToCurrentTime = true)
	{
		if (is_int($value) || is_float($value)) {
			return $value;
		}
		if (!is_string($value)) {
			throw new TInvalidDataValueException('date_to_parse_must_be_string', \Prado::varDump($value));
		}

		if (empty($this->pattern)) {
			return time();
		}

		if ($this->length(trim($value)) < 1) {
			return $defaultToCurrentTime ? time() : null;
		}

		$pattern = $this->pattern;

		$i_val = 0;
		$i_format = 0;
		$pattern_length = $this->length($pattern);
		$token = '';
		$x = null;
		$y = null;

		$year = null;
		$month = null;
		$day = null;

		while ($i_format < $pattern_length) {
			$c = $this->charAt($pattern, $i_format);
			$token = '';
			while ($this->charEqual($pattern, $i_format, $c)
						&& ($i_format < $pattern_length)) {
				$token .= $this->charAt($pattern, $i_format++);
			}

			switch($token)
			{
				case 'yyyy':
				case 'yy':
				case 'y':
				{
					if ($token == 'yyyy') {
						$x = 4;
						$y = 4;
					}
					if ($token == 'yy') {
						$x = 2;
						$y = 2;
					}
					if ($token == 'y') {
						$x = 2;
						$y = 4;
					}
					$year = $this->getInteger($value, $i_val, $x, $y);
					if ($year === null) {
						return null;
					}
					$i_val += strlen($year);
					if (strlen($year) == 2) {
						$iYear = (int) $year;
						if ($iYear > 70) {
							$year = $iYear + 1900;
						} else {
							$year = $iYear + 2000;
						}
					}
					$year = (int) $year;
					break;
				}
				case 'MM':
				case 'M':
				{
					$month = $this->getInteger(
						$value,
						$i_val,
						$this->length($token),
						2
					);
					$iMonth = (int) $month;
					if ($month === null || $iMonth < 1 || $iMonth > 12) {
						return null;
					}
					$i_val += strlen($month);
					$month = $iMonth;
					break;
				}
				case 'dd':
				case 'd':
				{
					$day = $this->getInteger(
						$value,
						$i_val,
						$this->length($token),
						2
					);
					$iDay = (int) $day;
					if ($day === null || $iDay < 1 || $iDay > 31) {
						return null;
					}
					$i_val += strlen($day);
					$day = $iDay;
					break;
				}
				default:
				{
					if ($this->substring($value, $i_val, $this->length($token)) != $token) {
						return null;
					}
					$i_val += $this->length($token);
					break;
				}
			}
		}

		if ($i_val != $this->length($value)) {
			return null;
		}

		if ($year === null) {
			// always default to current year if empty
			$year = date('Y');
		}
		if ($month === null) {
			$month = $defaultToCurrentTime ? date('m') : 1;
		}
		if ($day === null) {
			$day = $defaultToCurrentTime ? date('d') : 1;
		}

		$s = new \DateTime;
		$s->setDate($year, $month, $day);
		$s->setTime(0, 0, 0);
		return $s->getTimeStamp();
	}

	/**
	 * Calculate the length of a string, may be consider iconv_strlen?
	 * @param mixed $string
	 */
	private function length($string)
	{
		//use iconv_strlen or just strlen?
		return strlen($string);
	}

	/**
	 * Get the char at a position.
	 * @param mixed $string
	 * @param mixed $pos
	 */
	private function charAt($string, $pos)
	{
		return $this->substring($string, $pos, 1);
	}

	/**
	 * Gets a portion of a string, uses iconv_substr.
	 * @param mixed $string
	 * @param mixed $start
	 * @param mixed $length
	 */
	private function substring($string, $start, $length)
	{
		return iconv_substr($string, $start, $length);
	}

	/**
	 * Returns true if char at position equals a particular char.
	 * @param mixed $string
	 * @param mixed $pos
	 * @param mixed $char
	 */
	private function charEqual($string, $pos, $char)
	{
		return $this->charAt($string, $pos) == $char;
	}

	/**
	 * Gets integer from part of a string, allows integers of any length.
	 * @param string $str string to retrieve the integer from.
	 * @param int $i starting position
	 * @param int $minlength minimum integer length
	 * @param int $maxlength maximum integer length
	 * @return string integer portion of the string, null otherwise
	 */
	private function getInteger($str, $i, $minlength, $maxlength)
	{
		//match for digits backwards
		for ($x = $maxlength; $x >= $minlength; $x--) {
			$token = $this->substring($str, $i, $x);
			if ($this->length($token) < $minlength) {
				return null;
			}
			if (preg_match('/^\d+$/', $token)) {
				return $token;
			}
		}
		return null;
	}
}
