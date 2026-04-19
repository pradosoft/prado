<?php

/**
 * TSimpleDateFormatter class file
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

use Prado\Prado;
use Prado\Exceptions\TInvalidDataValueException;

/**
 * TSimpleDateFormatter class.
 *
 * Formats and parses dates using the SimpleDateFormat pattern.
 * This pattern is compatible with the I18N and java's SimpleDateFormatter.
 * ```
 * Pattern |      Description
 * ----------------------------------------------------
 * d       | Day of month 1 to 31, no padding
 * dd      | Day of month 01 to 31, zero padding
 * M       | Month 1 to 12, no padding
 * MM      | Month 01 to 12, zero padding
 * MMM     | Month abbreviated (Jan, Feb, etc.)
 * MMMM    | Full month name (January, etc.)
 * y       | Year (2 or 4 digits)
 * yy      | 2 digit year, e.g., 96, 05
 * yyyy    | 4 digit year, e.g., 2005
 * H       | Hour 0-23, no padding
 * HH      | Hour 00-23, zero padding
 * k       | Hour 1-24, no padding
 * kk      | Hour 01-24, zero padding
 * h       | Hour 1-12 (am/pm), no padding
 * hh      | Hour 01-12 (am/pm), zero padding
 * K       | Hour 0-11 (am/pm), no padding
 * KK      | Hour 00-11 (am/pm), zero padding
 * m       | Minute 0-59, no padding
 * mm      | Minute 00-59, zero padding
 * s       | Second 0-59, no padding
 * ss      | Second 00-59, zero padding
 * a       | AM/PM marker
 * E       | Day abbreviation (Mon, Tue)
 * EEEE    | Full day name (Monday, Tuesday)
 * D       | Day in year (1-366)
 * F       | Day of week in month
 * w       | Week in year
 * W       | Week in month
 * ----------------------------------------------------
 * ```
 *
 * Text literals can be included in patterns using single quotes.
 * The quoted text is preserved in formatted output and matched during parsing.
 * To include a literal single quote, use two single quotes ('').
 * ```php
 * $formatter = new TSimpleDateFormatter("yyyy 'Year' MM 'Month' dd 'Day'");
 * echo $formatter->format(time()); // "2026 Year 04 Month 17 Day"
 * ```
 *
 * Parsing behavior:
 * - Integer or float timestamps are returned unchanged.
 * - Missing components default to the current date/time (unless $defaultToCurrentTime is false).
 * - Two-digit years 00-70 become 2000-2070, 71-99 become 1971-1999.
 * - Invalid dates (e.g., Feb 30) return null.
 *
 * Usage example, to format a date
 * ```php
 * $formatter = new TSimpleDateFormatter("dd/MM/yyyy");
 * echo $formatter->format(time());
 * ```
 *
 * To parse the date string into a date timestamp.
 * ```php
 * $formatter = new TSimpleDateFormatter("d-M-yyyy");
 * echo $formatter->parse("24-6-2005");
 * ```
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @author Brad Anderson <belisoful@icloud.com> 2026 update
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

		$map = [
			'yyyy' => 'Y', 'yy' => 'y', 'y' => 'Y',
			'MMMM' => 'F', 'MMM' => 'M', 'MM' => 'm', 'M' => 'n',
			'dd' => 'd', 'd' => 'j',
			'kk' => 'G', 'k' => 'G',
			'KK' => 'H', 'K' => 'H',
			'HH' => 'H', 'H' => 'G',
			'hh' => 'h', 'h' => 'g',
			'mm' => 'i',
			'ss' => 's',
			'a' => 'A',
			'D' => 'z',
			'F' => 't',
			'w' => 'W',
			'W' => 'W',
		];

		$bits = [];
		foreach ($map as $pattern => $phpFormat) {
			$bits[$pattern] = $dt->format($phpFormat);
		}

		$bits['m'] = $dt->format('i');
		if (strlen($bits['m']) > 1 && $bits['m'][0] === '0') {
			$bits['m'] = substr($bits['m'], 1);
		}

		$bits['s'] = $dt->format('s');
		if (strlen($bits['s']) > 1 && $bits['s'][0] === '0') {
			$bits['s'] = substr($bits['s'], 1);
		}

		$kHour = (int) $dt->format('G');
		$kHour = $kHour === 0 ? 1 : $kHour;
		$bits['k'] = (string) $kHour;
		$bits['kk'] = sprintf('%02d', $kHour);

		$KHour = (int) $dt->format('G');
		$KHour = $KHour > 11 ? $KHour - 12 : $KHour;
		$bits['K'] = (string) $KHour;
		$bits['KK'] = sprintf('%02d', $KHour);

		$bits['E'] = $dt->format('D');
		$bits['EEEE'] = $dt->format('l');

		$sortedTokens = $bits;
		uksort($sortedTokens, function ($a, $b) {
			return strlen($b) - strlen($a);
		});

		$pattern = $this->pattern;
		$result = '';
		$idx = 0;
		while ($idx < strlen($pattern)) {
			if ($pattern[$idx] === "'") {
				$idx++;
				while ($idx < strlen($pattern) && $pattern[$idx] !== "'") {
					$result .= $pattern[$idx];
					$idx++;
				}
				if ($idx < strlen($pattern)) {
					$idx++;
				}
				continue;
			}
			$matched = false;
			foreach ($sortedTokens as $token => $replacement) {
				$tokenLen = strlen($token);
				if ($idx + $tokenLen <= strlen($pattern) && substr($pattern, $idx, $tokenLen) === $token) {
					$result .= $replacement;
					$idx += $tokenLen;
					$matched = true;
					break;
				}
			}
			if (!$matched) {
				$result .= $pattern[$idx];
				$idx++;
			}
		}
		return $result;
	}

	/**
	 * Get the month pattern from the formatting pattern.
	 * @return false|string M, MM, MMM, or MMMM if found, false otherwise.
	 */
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

	/**
	 * Get the day pattern from the formatting pattern.
	 * @return false|string d or dd if found, false otherwise.
	 */
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

	/**
	 * Get the year pattern from the formatting pattern.
	 * @return false|string yy or yyyy if found, false otherwise.
	 */
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

	/**
	 * Get the day, month, year ordering based on their positions in the pattern.
	 * @return array list of day, month, year in their pattern order.
	 */
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
	 * @return \DateTime date info
	 */
	private function getDate($value)
	{
		if (is_numeric($value)) {
			$date = new \DateTime();
			$date->setTimeStamp($value);
		} else {
			$date = new \DateTime($value);
		}
		return $date;
	}

	/**
	 * Validate if a date string matches the pattern and is a valid date.
	 * @param mixed $value date string to validate.
	 * @return bool true if valid and matches pattern, false otherwise.
	 */
	public function isValidDate($value)
	{
		return $this->parse($value, false) !== null;
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
		if (is_int($value)) {
			return $value;
		}
		if (is_float($value)) {
			return (int) $value;
		}
		if (!is_string($value)) {
			throw new TInvalidDataValueException('date_to_parse_must_be_string');
		}
		if (empty($this->pattern)) {
			return time();
		}
		if (strlen(trim($value)) < 1) {
			return $defaultToCurrentTime ? time() : null;
		}

		$i_val = 0;
		$i_format = 0;
		$pattern_length = strlen($this->pattern);
		$year = $month = $day = $hour = $minute = $second = null;
		$ampm = null;
		$hourMode = null;

		while ($i_format < $pattern_length) {
			if ($this->pattern[$i_format] === "'") {
				$startQuote = $i_format + 1;
				$i_format++;
				while ($i_format < $pattern_length && $this->pattern[$i_format] !== "'") {
					$i_format++;
				}
				$literalText = substr($this->pattern, $startQuote, $i_format - $startQuote);
				if (substr($value, $i_val, strlen($literalText)) !== $literalText) {
					return null;
				}
				$i_val += strlen($literalText);
				if ($i_format < $pattern_length) {
					$i_format++;
				}
				continue;
			}

			$c = $this->pattern[$i_format];
			$token = '';
			while ($i_format < $pattern_length && $this->pattern[$i_format] === $c) {
				$token .= $this->pattern[$i_format++];
			}

			switch ($token) {
				case 'yyyy':
					$year = $this->getInteger($value, $i_val, 4, 4);
					if ($year === null) {
						return null;
					}
					$i_val += strlen($year);
					break;
				case 'yy':
					$year = $this->getInteger($value, $i_val, 2, 2);
					if ($year === null) {
						return null;
					}
					$i_val += strlen($year);
					$yInt = (int) $year;
					$year = ($yInt > 70) ? $yInt + 1900 : $yInt + 2000;
					break;
				case 'y':
					$year = $this->getInteger($value, $i_val, 2, 4);
					if ($year === null) {
						return null;
					}
					$i_val += strlen($year);
					if (strlen($year) <= 2) {
						$yInt = (int) $year;
						$year = ($yInt > 70) ? $yInt + 1900 : $yInt + 2000;
					}
					break;
				case 'MMMM': case 'MMM': case 'MM': case 'M':
					$month = $this->getInteger($value, $i_val, strlen($token), 2);
					if ($month === null || $month < 1 || $month > 12) {
						return null;
					}
					$i_val += strlen($month);
					break;
				case 'dd': case 'd':
					$day = $this->getInteger($value, $i_val, strlen($token), 2);
					if ($day === null || $day < 1 || $day > 31) {
						return null;
					}
					$i_val += strlen($day);
					break;
				case 'kk': case 'k':
					$hour = $this->getInteger($value, $i_val, 1, 2);
					if ($hour === null || $hour < 1 || $hour > 24) {
						return null;
					}
					$i_val += strlen($hour);
					$hourMode = 'k';
					break;
				case 'HH': case 'H':
					$hour = $this->getInteger($value, $i_val, 1, 2);
					if ($hour === null || $hour > 23) {
						return null;
					}
					$i_val += strlen($hour);
					$hourMode = 'H';
					break;
				case 'KK': case 'K':
					$hour = $this->getInteger($value, $i_val, 1, 2);
					if ($hour === null || $hour > 11) {
						return null;
					}
					$i_val += strlen($hour);
					$hourMode = 'K';
					break;
				case 'hh': case 'h':
					$hour = $this->getInteger($value, $i_val, 1, 2);
					if ($hour === null || $hour < 1 || $hour > 12) {
						return null;
					}
					$i_val += strlen($hour);
					$hourMode = 'h';
					break;
				case 'mm': case 'm':
					$minute = $this->getInteger($value, $i_val, 1, 2);
					if ($minute === null || $minute > 59) {
						return null;
					}
					$i_val += strlen($minute);
					break;
				case 'ss': case 's':
					$second = $this->getInteger($value, $i_val, 1, 2);
					if ($second === null || $second > 59) {
						return null;
					}
					$i_val += strlen($second);
					break;
				case 'D':
					$dayInYear = $this->getInteger($value, $i_val, 1, 3);
					if ($dayInYear === null) {
						return null;
					}
					$i_val += strlen($dayInYear);
					break;
				case 'a':
					$sub = substr($value, $i_val, 2);
					$ampm = strtoupper($sub);
					if ($ampm !== 'AM' && $ampm !== 'PM') {
						return null;
					}
					$i_val += 2;
					break;
				default:
					if (substr($value, $i_val, strlen($token)) !== $token) {
						return null;
					}
					$i_val += strlen($token);
					break;
			}
		}

		if ($i_val != strlen($value)) {
			return null;
		}

		$year = ($year === null) ? (int) date('Y') : (int) $year;
		$month = ($month === null) ? ($defaultToCurrentTime ? (int) date('m') : 1) : (int) $month;
		$day = ($day === null) ? ($defaultToCurrentTime ? (int) date('d') : 1) : (int) $day;

		if (!checkdate($month, $day, $year)) {
			return null;
		}

		$hour = $hour !== null ? (int) $hour : 0;

		if ($hourMode === 'k') {
			$hour = $hour - 1;
		} elseif ($hourMode === 'K') {
		} elseif ($hourMode === 'h' && $ampm !== null) {
			if ($ampm === 'PM' && $hour < 12) {
				$hour += 12;
			}
			if ($ampm === 'AM' && $hour === 12) {
				$hour = 0;
			}
		} elseif ($ampm !== null) {
			if ($ampm === 'PM' && $hour < 12) {
				$hour += 12;
			}
			if ($ampm === 'AM' && $hour === 12) {
				$hour = 0;
			}
		}

		$s = new \DateTime();
		$s->setDate($year, $month, $day);
		$s->setTime($hour, (int) $minute, (int) $second);
		return $s->getTimestamp();
	}

	/**
	 * Get integer from a string at a given position.
	 * @param string $str string to extract integer from.
	 * @param int $i starting position.
	 * @param int $minlength minimum length of integer.
	 * @param int $maxlength maximum length of integer.
	 * @return false|string integer string if found, false otherwise.
	 */
	private function getInteger($str, $i, $minlength, $maxlength)
	{
		for ($x = $maxlength; $x >= $minlength; $x--) {
			$token = substr($str, $i, $x);
			if (strlen($token) >= $minlength && preg_match('/^\d+$/', $token)) {
				return $token;
			}
		}
		return null;
	}
}
